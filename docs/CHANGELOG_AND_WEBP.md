# Changelog & WebP Image Conversion

Implementation reference for two platform improvements:

1. **Model Changelog** — SAP-inspired audit trail on critical models, accessible from the admin action dropdown
2. **WebP Image Conversion** — Automatic WebP generation at upload time using Intervention Image v3

---

## Table of Contents

1. [Model Changelog](#1-model-changelog)
   - [Architecture Overview](#architecture-overview)
   - [Critical Models & Tracked Fields](#critical-models--tracked-fields)
   - [Adding Changelog to a New Model](#adding-changelog-to-a-new-model)
   - [Changelog Pages](#changelog-pages)
   - [Admin Dropdown Integration](#admin-dropdown-integration)
2. [WebP Image Conversion](#2-webp-image-conversion)
   - [Architecture Overview](#architecture-overview-1)
   - [ImageService](#imageservice)
   - [x-webp-image Component](#x-webp-image-component)
   - [Database Columns](#database-columns)
   - [Adding WebP to a New Upload Form](#adding-webp-to-a-new-upload-form)
3. [Testing](#3-testing)

---

## 1. Model Changelog

### Architecture Overview

```
Admin listing page
  └── Action dropdown (flux:dropdown)
        └── "Change Log" menu item  ──► /admin/catalog/products/{product}/changelog
                                              └── Livewire 4 anonymous class page
                                                    └── Activity::forSubject($model)
                                                          └── Spatie activity_log table
```

**Key files:**

| File | Purpose |
|------|---------|
| `app/Traits/LogsModelChanges.php` | Wrapper trait — apply to any model to enable tracking |
| `config/activitylog.php` | Spatie config — retention 365 days, enabled via `ACTIVITY_LOGGER_ENABLED` |
| `database/migrations/*_create_activity_log_table.php` | Already exists — no new migration needed |
| `resources/views/pages/admin/*/changelog.blade.php` | Standalone changelog pages (one per model) |

---

### Critical Models & Tracked Fields

| Model | Trait Applied | Fields Tracked | Log Name |
|-------|--------------|----------------|----------|
| `Product` | `LogsModelChanges` | `name`, `price`, `sale_price`, `sku`, `stock_quantity`, `is_active`, `status`, `category_id`, `brand_id` | `product` |
| `ProductVariant` | `LogsModelChanges` | `sku`, `price`, `sale_price`, `stock_quantity`, `is_active` | `product_variant` |
| `Order` | `LogsModelChanges` | `status`, `payment_status`, `notes` | `order` |
| `Quote` | `LogsModelChanges` | `status`, `notes` | `quote` |
| `User` | `LogsModelChanges` | `name`, `email`, `is_active` | `user` |
| `Category` | `LogsModelChanges` | `name`, `parent_id`, `is_active`, `sort_order` | `category` |
| `Brand` | `LogsModelChanges` | `name`, `is_active` | `brand` |

> For deeper context on Spatie Activity Log patterns (manual logging, best practices, querying), see [`docs/ACTIVITY_LOGGING_GUIDE.md`](./ACTIVITY_LOGGING_GUIDE.md).

---

### Adding Changelog to a New Model

**Step 1 — Add the trait:**

```php
use App\Traits\LogsModelChanges;

class ShippingMethod extends Model
{
    use LogsModelChanges;

    protected function getLoggedAttributes(): array
    {
        return ['name', 'price', 'is_active'];
    }

    // Optional: customise the log name (defaults to lowercase class name)
    protected function getLogName(): string
    {
        return 'shipping_method';
    }
}
```

The `LogsModelChanges` trait automatically applies `logOnlyDirty()` and `dontSubmitEmptyLogs()` — only genuine changes create log entries.

**Step 2 — Create the changelog page:**

```bash
php artisan make:livewire pages::admin.logistics.shipping-methods.changelog --no-interaction
```

Follow the same Livewire 4 anonymous class structure as the existing changelog pages (see below).

**Step 3 — Add the dropdown entry:**

```blade
<flux:menu.item icon="clock" icon-variant="outline"
    href="{{ route('admin.logistics.shipping-methods.changelog', $method) }}" wire:navigate>
    Change Log
</flux:menu.item>
```

---

### Changelog Pages

Each page is a **Livewire 4 anonymous class single-file component** — the same pattern used across the admin (inline `new class extends Component` in the Blade file). Not Volt.

**Pages created:**

```
resources/views/pages/admin/catalog/products/changelog.blade.php
resources/views/pages/admin/sales/orders/changelog.blade.php
resources/views/pages/admin/sales/quotations/changelog.blade.php
resources/views/pages/admin/access/users/changelog.blade.php
resources/views/pages/admin/catalog/categories/changelog.blade.php
resources/views/pages/admin/catalog/brands/changelog.blade.php
```

**Page structure:**

```php
<?php
use App\Models\Product;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new #[Title('Product Change Log')] class extends Component {
    use WithPagination;

    public Product $product;

    public function mount(Product $product): void
    {
        $this->authorize('view', $product);
        $this->product = $product;
    }

    public function render(): \Illuminate\View\View
    {
        $activities = Activity::forSubject($this->product)
            ->with('causer')
            ->latest()
            ->paginate(20);

        return view('...', compact('activities'));
    }
};
?>
```

**Rendering field diffs in the view:**

The `properties` column on each `Activity` stores old and new values:

```php
// $activity->properties structure for an 'updated' event:
// {
//   "old": { "price": "1200.00" },
//   "attributes": { "price": "1450.00" }
// }

$old  = $activity->properties->get('old', []);
$new  = $activity->properties->get('attributes', []);
$diff = array_keys(array_merge($old, $new));
```

```blade
@foreach ($diff as $field)
    <tr>
        <td>{{ $field }}</td>
        <td class="text-red-600">{{ $old[$field] ?? '—' }}</td>
        <td>→</td>
        <td class="text-green-600">{{ $new[$field] ?? '—' }}</td>
    </tr>
@endforeach
```

---

### Admin Dropdown Integration

Products and Orders already use `<flux:dropdown>` + `<flux:menu>`. Categories and Brands previously used plain icon buttons and were converted to dropdowns to accommodate the new item.

**Dropdown entry pattern:**

```blade
<flux:menu.separator />
<flux:menu.item
    icon="clock"
    icon-variant="outline"
    href="{{ route('admin.catalog.products.changelog', $product) }}"
    wire:navigate>
    Change Log
</flux:menu.item>
```

**Listing pages updated:**

| Page | File |
|------|------|
| Products | `resources/views/pages/admin/catalog/products/index.blade.php` |
| Orders | `resources/views/pages/admin/sales/orders/index.blade.php` |
| Quotations | `resources/views/pages/admin/sales/quotations/index.blade.php` |
| Users | `resources/views/pages/admin/access/users/index.blade.php` |
| Categories | `resources/views/pages/admin/catalog/categories/index.blade.php` |
| Brands | `resources/views/pages/admin/catalog/brands/index.blade.php` |

---

## 2. WebP Image Conversion

### Architecture Overview

```
Admin uploads image (Livewire form)
  └── ImageService::storeWithWebP()
        ├── Store original  → storage/app/public/products/images/abc.jpg
        └── Convert + store → storage/app/public/products/images/abc.webp

Frontend view
  └── <x-webp-image :src="$product->image_url" :webp="$product->webp_image_url" />
        └── <picture>
              <source type="image/webp" srcset="...abc.webp">
              <img src="...abc.jpg" alt="...">
            </picture>
```

**Package chosen:** `intervention/image` v3

Spatie Media Library was evaluated and ruled out — it replaces the entire image storage architecture (polymorphic `media` table, media collections, replaces all image columns) which is a far larger refactor than needed. Intervention Image converts images in 3 lines with zero disruption to the existing upload flow.

---

### ImageService

**File:** `app/Services/ImageService.php`

```php
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageService
{
    /**
     * Store an image and generate a WebP variant alongside it.
     *
     * @return array{original: string, webp: string}
     */
    public function storeWithWebP(
        TemporaryUploadedFile $file,
        string $directory,
        string $disk = 'public'
    ): array {
        // 1. Store the original (existing behaviour)
        $originalPath = $file->store($directory, $disk);

        // 2. Convert to WebP
        $manager  = new ImageManager(new Driver());
        $image    = $manager->read($file->getRealPath());
        $encoded  = $image->toWebp(quality: 85);

        // 3. Store WebP at same path with .webp extension
        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $originalPath);
        Storage::disk($disk)->put($webpPath, $encoded);

        return [
            'original' => $originalPath,
            'webp'     => $webpPath,
        ];
    }
}
```

**Resolve it in Livewire forms:**

```php
$imageService = app(ImageService::class);
$paths = $imageService->storeWithWebP($this->image, 'products/images');
// $paths['original'] → 'products/images/abc.jpg'
// $paths['webp']     → 'products/images/abc.webp'
```

---

### x-webp-image Component

**File:** `resources/views/components/webp-image.blade.php`

```blade
@props(['src', 'webp' => null, 'alt' => '', 'class' => ''])

@if ($webp)
<picture>
    <source type="image/webp" srcset="{{ $webp }}">
    <img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => $class]) }}>
</picture>
@else
<img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => $class]) }}>
@endif
```

**Usage:**

```blade
{{-- With WebP (new uploads) --}}
<x-webp-image
    :src="$product->image_url"
    :webp="$product->webp_image_url"
    alt="{{ $product->name }}"
    class="w-full object-contain" />

{{-- Without WebP (old images, graceful fallback) --}}
<x-webp-image
    :src="$product->image_url"
    alt="{{ $product->name }}"
    class="w-full object-contain" />
```

The component degrades cleanly: if `$webp` is null (existing images not yet re-uploaded), it renders a plain `<img>` tag.

---

### Database Columns

**Migration:** `add_webp_columns_to_image_tables`

| Table | Column | Type |
|-------|--------|------|
| `products` | `image_webp` | `nullable string` |
| `product_images` | `webp_path` | `nullable string` |
| `brands` | `logo_webp` | `nullable string` |
| `categories` | `image_webp` | `nullable string` |
| `categories` | `icon_webp` | `nullable string` |

**Model accessors (pattern):**

```php
// Product.php
public function getWebpImageUrlAttribute(): ?string
{
    return $this->image_webp ? asset('storage/' . $this->image_webp) : null;
}

// ProductImage.php
public function getWebpUrlAttribute(): ?string
{
    return $this->webp_path ? asset('storage/' . $this->webp_path) : null;
}
```

---

### Adding WebP to a New Upload Form

Any future upload that goes through a Livewire form can use `ImageService`:

```php
use App\Services\ImageService;

// In your Livewire form/component:
$paths = app(ImageService::class)->storeWithWebP(
    $this->image,        // TemporaryUploadedFile
    'your/directory',    // storage subdirectory
    'public'             // disk (default)
);

$model->update([
    'image'      => $paths['original'],
    'image_webp' => $paths['webp'],
]);
```

**Views updated to use `<x-webp-image>`:**

| File | What changed |
|------|-------------|
| `resources/views/components/product-card.blade.php` | Main product thumbnail |
| `resources/views/pages/product-details/partials/_hero.blade.php` | Product gallery |
| `resources/views/pages/product-details/partials/_grouped-hero.blade.php` | Grouped product gallery |

---

## 3. Testing

### Changelog

```php
// Test trait activates and logs changes
it('logs product price change', function () {
    $product = Product::factory()->create(['price' => 1000]);

    $product->update(['price' => 1500]);

    $activity = \Spatie\Activitylog\Models\Activity::forSubject($product)->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties['old']['price'])->toBe('1000.00')
        ->and($activity->properties['attributes']['price'])->toBe('1500.00');
});

// Test empty logs are not created
it('does not log when no tracked field changes', function () {
    $product = Product::factory()->create();

    $product->update(['updated_at' => now()]);

    expect(\Spatie\Activitylog\Models\Activity::forSubject($product)->count())->toBe(1); // only created
});
```

### WebP

```php
// Test ImageService generates both files
it('stores original and webp on upload', function () {
    Storage::fake('public');

    $file    = UploadedFile::fake()->image('product.jpg', 800, 600);
    $service = new \App\Services\ImageService();
    $paths   = $service->storeWithWebP($file, 'products/images');

    Storage::disk('public')->assertExists($paths['original']);
    Storage::disk('public')->assertExists($paths['webp']);
    expect($paths['webp'])->toEndWith('.webp');
});
```

**Run targeted tests:**

```bash
php artisan test --compact --filter=ActivityLog
php artisan test --compact --filter=ImageService
php artisan test --compact   # full suite
```
