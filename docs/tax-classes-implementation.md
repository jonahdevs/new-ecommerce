# Tax Classes Implementation Plan

## Overview

Add per-product tax class support so individual products can override the global tax rate.
The global `TaxSettings` remains the master switch and fallback — tax classes only override the rate.

**Resolution order:**
1. `tax_enabled = false` → 0% for everything
2. Product has a `tax_class_id` → use that class's rate
3. Product has no tax class → fall back to `TaxSettings::tax_rate`

---

## Step 1 — Create TaxClass Model, Migration & Factory

```bash
php artisan make:model TaxClass -mf --no-interaction
```

### Migration: `create_tax_classes_table`

```php
Schema::create('tax_classes', function (Blueprint $table) {
    $table->id();
    $table->string('name');                     // e.g. "Standard Rate", "Zero-Rated", "Exempt"
    $table->decimal('rate', 5, 2)->default(0);  // e.g. 16.00 for 16%, 0.00 for exempt
    $table->text('description')->nullable();
    $table->timestamps();
});
```

### Model: `app/Models/TaxClass.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxClass extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'rate', 'description'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function rateLabel(): string
    {
        return rtrim(rtrim(number_format($this->rate, 2), '0'), '.') . '%';
    }
}
```

### Factory: `database/factories/TaxClassFactory.php`

```php
public function definition(): array
{
    return [
        'name' => $this->faker->randomElement(['Standard Rate', 'Reduced Rate', 'Zero-Rated', 'Exempt']),
        'rate' => $this->faker->randomElement([16.00, 8.00, 0.00]),
        'description' => $this->faker->optional()->sentence(),
    ];
}
```

---

## Step 2 — Add `tax_class_id` to Products

```bash
php artisan make:migration add_tax_class_id_to_products_table --no-interaction
```

```php
Schema::table('products', function (Blueprint $table) {
    $table->foreignId('tax_class_id')->nullable()->constrained('tax_classes')->nullOnDelete()->after('brand_id');
});
```

---

## Step 3 — Update Product Model

In `app/Models/Product.php`:

**Add to `$fillable`:**
```php
'tax_class_id',
```

**Add relationship:**
```php
public function taxClass(): BelongsTo
{
    return $this->belongsTo(TaxClass::class);
}
```

**Add import at top:**
```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
```

---

## Step 4 — Update TaxService

Modify `calculateTax()` to accept an optional `Product` and resolve its effective rate.

```php
/**
 * Resolve the effective tax rate for a product (as decimal, e.g. 0.16).
 * Falls back to global rate if the product has no tax class.
 */
public function effectiveRate(?Product $product = null): float
{
    if ($product?->taxClass) {
        return $product->taxClass->rate / 100;
    }

    return $this->rate(); // global fallback
}

/**
 * Calculate tax for a given amount in cents, optionally scoped to a product.
 */
public function calculateTax(int $amountCents, ?Product $product = null): int
{
    if (!$this->isEnabled() || $amountCents <= 0) {
        return 0;
    }

    $rate = $this->effectiveRate($product);

    if ($this->isInclusive()) {
        return (int) round($amountCents - ($amountCents / (1 + $rate)));
    }

    return (int) round($amountCents * $rate);
}
```

Add import at top of TaxService:
```php
use App\Models\Product;
```

---

## Step 5 — Update CartService

In `app/Services/CartService.php`, the `summary()` method currently calculates tax on the whole subtotal at once. Update it to sum per-line-item taxes.

Find the block around line 382:
```php
$taxableAmountCents = (int) round(($subtotal - $discount) * 100);
$taxCents = $taxService->calculateTax($taxableAmountCents);
```

Replace with:
```php
// Calculate tax per line item using each product's effective rate
$taxCents = 0;
foreach ($this->items() as $item) {
    $lineSubtotal = $item['final_price'] * $item['quantity'];
    $lineSubtotalCents = (int) round($lineSubtotal * 100);
    // Apply proportional discount if any
    if ($subtotal > 0 && $discount > 0) {
        $lineSubtotalCents = (int) round($lineSubtotalCents * (1 - ($discount / $subtotal)));
    }
    $product = isset($item['product_id']) ? \App\Models\Product::find($item['product_id']) : null;
    $taxCents += $taxService->calculateTax($lineSubtotalCents, $product);
}
```

> **Note:** Check how `items()` is structured in `CartService` to confirm the exact field names (`product_id`, `final_price`, `quantity`). Adjust accordingly.

---

## Step 6 — Seed Default Tax Classes

Create or update `database/seeders/TaxClassSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\TaxClass;
use Illuminate\Database\Seeder;

class TaxClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            [
                'name' => 'Standard Rate',
                'rate' => 16.00,
                'description' => 'Default VAT rate. Applies to most products.',
            ],
            [
                'name' => 'Reduced Rate',
                'rate' => 8.00,
                'description' => 'Lower VAT rate for qualifying goods.',
            ],
            [
                'name' => 'Zero-Rated',
                'rate' => 0.00,
                'description' => 'Taxable supply but at 0% — still VAT-registered.',
            ],
            [
                'name' => 'Exempt',
                'rate' => 0.00,
                'description' => 'Outside the scope of VAT entirely.',
            ],
        ];

        foreach ($classes as $class) {
            TaxClass::firstOrCreate(['name' => $class['name']], $class);
        }
    }
}
```

Register in `DatabaseSeeder.php`:
```php
$this->call(TaxClassSeeder::class);
```

Run:
```bash
php artisan db:seed --class=TaxClassSeeder --no-interaction
```

---

## Step 7 — Admin UI: Tax Classes Management Page

**Route location:** `resources/views/pages/admin/settings/tax-classes.blade.php`
(Folio file-based routing — it will be at `/admin/settings/tax-classes`)

**Add to sidebar** under Settings & Others in `resources/views/layouts/app/sidebar.blade.php`:
```blade
<flux:navlist.item icon="receipt-tax" wire:navigate :href="route('admin.settings.tax-classes')"
    :current="request()->routeIs('admin.settings.tax-classes')">
    Tax Classes
</flux:navlist.item>
```
> Check if a named route exists or if Folio auto-generates it; use the path directly if needed.

**Page structure** (follow the pattern of `zones.blade.php`):
- PHP class with `WithPagination`, `#[Computed] taxClasses()`, `openCreate()`, `save()`, `edit()`, `confirmDelete()`, `delete()`
- `TaxClassForm` Livewire form object at `app/Livewire/Forms/Admin/TaxClassForm.php`
- Table columns: Name | Rate | Description | Products Count | Actions
- Create/Edit modal with: Name (text), Rate (number, step 0.01), Description (textarea, optional)
- Delete confirmation modal — block if products are assigned

### TaxClassForm

```php
<?php

namespace App\Livewire\Forms\Admin;

use App\Models\TaxClass;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TaxClassForm extends Form
{
    public ?TaxClass $taxClass = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|numeric|min:0|max:100')]
    public string $rate = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    public function setTaxClass(TaxClass $taxClass): void
    {
        $this->taxClass = $taxClass;
        $this->name = $taxClass->name;
        $this->rate = $taxClass->rate;
        $this->description = $taxClass->description ?? '';
    }

    public function store(): void
    {
        $this->validate();

        TaxClass::create([
            'name' => $this->name,
            'rate' => $this->rate,
            'description' => $this->description ?: null,
        ]);
    }

    public function update(): void
    {
        $this->validate();

        $this->taxClass->update([
            'name' => $this->name,
            'rate' => $this->rate,
            'description' => $this->description ?: null,
        ]);
    }
}
```

---

## Step 8 — Admin Product Form: Tax Class Dropdown

In the product edit/create form, find the **Pricing** section and add a Tax Class selector.

File: `resources/views/pages/admin/catalog/products/[Product]/edit.blade.php` (or wherever the pricing fields live).

```blade
<flux:select wire:model="form.tax_class_id" label="Tax Class" placeholder="Use global rate ({{ $globalTaxRate }})" clearable>
    @foreach ($this->taxClasses as $class)
        <flux:select.option value="{{ $class->id }}">
            {{ $class->name }} — {{ $class->rateLabel() }}
        </flux:select.option>
    @endforeach
</flux:select>
```

Add to the product page's PHP class:
```php
#[Computed]
public function taxClasses()
{
    return \App\Models\TaxClass::orderBy('name')->get();
}

public function globalTaxRate(): string
{
    return app(\App\Services\TaxService::class)->rateLabel();
}
```

Add `tax_class_id` to the product form object's fillable fields and validation rules.

---

## Step 9 — Run Tests

```bash
php artisan test --compact --filter=Tax
```

Write or update tests in `tests/Feature/`:
- `TaxClassTest.php` — CRUD via admin page
- `TaxServiceTest.php` — `calculateTax()` with/without product, with/without class

---

## Files to Create/Modify Summary

| Action | File |
|--------|------|
| CREATE | `database/migrations/xxxx_create_tax_classes_table.php` |
| CREATE | `database/migrations/xxxx_add_tax_class_id_to_products_table.php` |
| CREATE | `app/Models/TaxClass.php` |
| CREATE | `database/factories/TaxClassFactory.php` |
| CREATE | `database/seeders/TaxClassSeeder.php` |
| CREATE | `app/Livewire/Forms/Admin/TaxClassForm.php` |
| CREATE | `resources/views/pages/admin/settings/tax-classes.blade.php` |
| MODIFY | `app/Models/Product.php` — add `tax_class_id` to fillable + `taxClass()` relationship |
| MODIFY | `app/Services/TaxService.php` — add `effectiveRate(?Product)`, update `calculateTax()` |
| MODIFY | `app/Services/CartService.php` — per-line-item tax calculation |
| MODIFY | `resources/views/layouts/app/sidebar.blade.php` — add Tax Classes link |
| MODIFY | Product form page — add tax class dropdown in pricing section |
| MODIFY | `database/seeders/DatabaseSeeder.php` — call `TaxClassSeeder` |

---

## Notes

- The `CheckoutService` likely calls `CartService::summary()` — once CartService is updated the order tax will automatically be correct. Verify this before assuming.
- Keep the `TaxSettings::tax_rate` as the global fallback/default. "Standard Rate" tax class should match it but is still a separate override.
- The distinction between Zero-Rated (0%) and Exempt (0%) is accounting/reporting only — both result in 0 tax in the calculation engine. You may want to store a boolean `is_exempt` on `TaxClass` if you need to distinguish them in reports later.
