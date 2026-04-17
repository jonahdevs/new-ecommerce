# Activity Logging Guide

This guide explains how to implement model change tracking in your e-commerce system using Spatie Activity Log.

## 📚 Table of Contents

1. [Overview](#overview)
2. [Approaches](#approaches)
3. [Implementation Examples](#implementation-examples)
4. [Best Practices](#best-practices)
5. [Querying Activity Logs](#querying-activity-logs)
6. [Admin UI Integration](#admin-ui-integration)

---

## Overview

Your system uses **Spatie Activity Log** for tracking changes and events. There are two main approaches:

### Current Usage (Manual Logging)

You're already using manual logging for:

- ✅ SAP sync events
- ✅ Payment events
- ✅ Inventory operations
- ✅ Webhook events

### New Feature (Automatic Model Tracking)

Add automatic tracking for model changes using the `LogsActivity` trait.

---

## Approaches

### Approach 1: Automatic Logging (Use LogsActivity Trait)

**When to use:**

- Models where you need complete audit trail
- Critical business data (Products, Orders, Users)
- Compliance requirements (price changes, user modifications)

**Example:**

```php
use App\Traits\LogsModelChanges;

class Product extends Model
{
    use LogsModelChanges;

    // Automatically logs all changes to fillable attributes
}
```

**What gets logged:**

- ✅ All attribute changes (old → new values)
- ✅ Who made the change (causer)
- ✅ When it happened (timestamp)
- ✅ What changed (properties)

---

### Approach 2: Manual Logging (Use activity() Helper)

**When to use:**

- Business events (not just model changes)
- Custom workflows
- External integrations
- Complex operations

**Example:**

```php
activity()
    ->performedOn($order)
    ->causedBy(auth()->user())
    ->withProperties([
        'sap_document' => 'DOC-001',
        'attempt' => 1
    ])
    ->log('sap_sync_completed');
```

**What gets logged:**

- ✅ Custom event name
- ✅ Custom properties
- ✅ Related model
- ✅ Who triggered it

---

### Approach 3: Hybrid (RECOMMENDED)

Combine both approaches for maximum flexibility:

```php
class Product extends Model
{
    use LogsModelChanges; // Automatic tracking

    public function publish()
    {
        $this->update(['status' => ProductStatus::PUBLISHED]);

        // Manual log for business event
        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['previous_status' => $this->getOriginal('status')])
            ->log('product_published');
    }
}
```

---

## Implementation Examples

### 1. Product Model (Full Tracking)

```php
<?php

namespace App\Models;

use App\Traits\LogsModelChanges;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use LogsModelChanges;

    /**
     * Customize what gets logged
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'sku',
                'price',
                'sale_price',
                'stock_quantity',
                'status',
                'manage_stock',
            ])
            ->logOnlyDirty() // Only log changed attributes
            ->dontSubmitEmptyLogs()
            ->useLogName('product');
    }

    /**
     * Custom descriptions for events
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        return match($eventName) {
            'created' => "Product '{$this->name}' created",
            'updated' => "Product '{$this->name}' updated",
            'deleted' => "Product '{$this->name}' deleted",
            default => "Product {$eventName}",
        };
    }
}
```

### 2. Order Model (Selective Tracking)

```php
<?php

namespace App\Models;

use App\Traits\LogsModelChanges;
use Spatie\Activitylog\LogOptions;

class Order extends Model
{
    use LogsModelChanges;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'payment_status',
                'total_cents',
                'shipping_address',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('order');
    }

    /**
     * Don't log sensitive data
     */
    protected function getLoggedAttributes(): array
    {
        return [
            'status',
            'payment_status',
            'total_cents',
            // Exclude: billing_address (may contain sensitive data)
        ];
    }
}
```

### 3. User Model (Security Tracking)

```php
<?php

namespace App\Models;

use App\Traits\LogsModelChanges;
use Spatie\Activitylog\LogOptions;

class User extends Model
{
    use LogsModelChanges;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'status',
                'is_staff',
                'suspended_until',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match($eventName) {
            'created' => "User account created: {$this->email}",
            'updated' => "User account updated: {$this->email}",
            'deleted' => "User account deleted: {$this->email}",
            default => "User {$eventName}",
        };
    }
}
```

### 4. Settings Model (Configuration Tracking)

```php
<?php

namespace App\Models;

use App\Traits\LogsModelChanges;
use Spatie\Activitylog\LogOptions;

class Setting extends Model
{
    use LogsModelChanges;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Log everything for settings
            ->logOnlyDirty()
            ->useLogName('settings');
    }
}
```

---

## Best Practices

### ✅ DO

1. **Log critical business data**
    - Price changes
    - Stock updates
    - Order status changes
    - User role changes

2. **Use descriptive log names**

    ```php
    ->useLogName('product')  // Good
    ->useLogName('default')  // Bad
    ```

3. **Add context with properties**

    ```php
    activity()
        ->withProperties([
            'reason' => 'Bulk price update',
            'affected_count' => 150
        ])
        ->log('bulk_price_update');
    ```

4. **Use causedBy for accountability**

    ```php
    activity()
        ->causedBy(auth()->user())
        ->log('action_performed');
    ```

5. **Clean old logs regularly**
    ```bash
    php artisan activitylog:clean
    ```

### ❌ DON'T

1. **Don't log sensitive data**
    - Passwords
    - Credit card numbers
    - API keys
    - Personal identification numbers

2. **Don't log everything**
    - Timestamps (created_at, updated_at)
    - Computed fields
    - Temporary data

3. **Don't forget to exclude attributes**

    ```php
    // Bad - logs password changes
    ->logAll()

    // Good - excludes password
    ->logOnly(['name', 'email', 'status'])
    ```

4. **Don't use automatic logging for high-frequency updates**
    - View counts
    - Click tracking
    - Real-time analytics

---

## Querying Activity Logs

### Get all activities for a model

```php
$activities = Activity::forSubject($product)->get();
```

### Get activities by causer (who did it)

```php
$activities = Activity::causedBy($user)->get();
```

### Get activities by log name

```php
$activities = Activity::inLog('product')->get();
```

### Get activities by event

```php
$activities = Activity::where('description', 'product_published')->get();
```

### Get recent activities

```php
$activities = Activity::latest()->limit(50)->get();
```

### Get activities with changes

```php
$activities = Activity::forSubject($product)
    ->get()
    ->map(function ($activity) {
        return [
            'event' => $activity->description,
            'changes' => $activity->changes(),
            'causer' => $activity->causer?->name,
            'created_at' => $activity->created_at,
        ];
    });
```

### Get specific attribute changes

```php
$priceChanges = Activity::forSubject($product)
    ->get()
    ->filter(function ($activity) {
        return isset($activity->changes()['attributes']['price']);
    });
```

---

## Admin UI Integration

### 1. Activity Log Index Page

Create a page to view all activities:

```php
// routes/web.php
Route::get('/admin/activity-logs', ActivityLogController::class)
    ->name('admin.activity-logs.index');
```

### 2. Model-Specific Activity Tab

Add activity log to product edit page:

```blade
{{-- resources/views/pages/admin/catalog/products/edit.blade.php --}}

<flux:tab name="activity">Activity Log</flux:tab>

<flux:tab.panel name="activity">
    <div class="space-y-4">
        @foreach($product->activities as $activity)
            <div class="border rounded p-4">
                <div class="flex justify-between">
                    <span class="font-semibold">{{ $activity->description }}</span>
                    <span class="text-sm text-gray-500">
                        {{ $activity->created_at->diffForHumans() }}
                    </span>
                </div>

                @if($activity->causer)
                    <div class="text-sm text-gray-600">
                        by {{ $activity->causer->name }}
                    </div>
                @endif

                @if($changes = $activity->changes())
                    <div class="mt-2 text-sm">
                        @foreach($changes['attributes'] ?? [] as $key => $new)
                            <div class="flex gap-2">
                                <span class="font-medium">{{ $key }}:</span>
                                <span class="text-red-600">
                                    {{ $changes['old'][$key] ?? 'null' }}
                                </span>
                                <span>→</span>
                                <span class="text-green-600">{{ $new }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</flux:tab.panel>
```

### 3. Dashboard Widget

Show recent activities on dashboard:

```php
// In dashboard component
public function recentActivities()
{
    return Activity::with(['subject', 'causer'])
        ->latest()
        ->limit(10)
        ->get();
}
```

---

## Recommended Models to Track

### High Priority (Automatic Logging)

- ✅ **Product** - Price, stock, status changes
- ✅ **Order** - Status, payment status changes
- ✅ **User** - Profile, role, status changes
- ✅ **Category** - Name, status changes
- ✅ **Brand** - Name, status changes

### Medium Priority (Selective Logging)

- ⚠️ **ShippingMethod** - Rate changes
- ⚠️ **TaxClass** - Rate changes
- ⚠️ **Coupon** - Discount changes

### Low Priority (Manual Logging Only)

- ❌ **Cart** - Too frequent
- ❌ **WishlistItem** - Too frequent
- ❌ **Review** - Use manual logging for moderation events

---

## Performance Considerations

### Database Impact

Each logged change creates a row in `activity_log` table:

- **Low impact**: 1-10 changes/minute
- **Medium impact**: 10-100 changes/minute
- **High impact**: 100+ changes/minute

### Optimization Tips

1. **Use logOnlyDirty()**

    ```php
    ->logOnlyDirty() // Only log if value actually changed
    ```

2. **Limit logged attributes**

    ```php
    ->logOnly(['price', 'stock_quantity']) // Not all attributes
    ```

3. **Clean old logs**

    ```bash
    # In app/Console/Kernel.php
    $schedule->command('activitylog:clean')->daily();
    ```

4. **Index the table**
    ```php
    Schema::table('activity_log', function (Blueprint $table) {
        $table->index(['subject_type', 'subject_id']);
        $table->index('causer_id');
        $table->index('created_at');
    });
    ```

---

## Testing

### Test automatic logging

```php
test('product price change is logged', function () {
    $product = Product::factory()->create(['price' => 100]);

    $product->update(['price' => 150]);

    $activity = Activity::forSubject($product)->first();

    expect($activity->changes()['attributes']['price'])->toBe(150);
    expect($activity->changes()['old']['price'])->toBe(100);
});
```

### Test manual logging

```php
test('sap sync is logged', function () {
    $order = Order::factory()->create();

    activity()
        ->performedOn($order)
        ->withProperties(['sap_document' => 'DOC-001'])
        ->log('sap_sync_completed');

    $activity = Activity::forSubject($order)
        ->where('description', 'sap_sync_completed')
        ->first();

    expect($activity->properties['sap_document'])->toBe('DOC-001');
});
```

---

## Summary

**For your e-commerce system, I recommend:**

1. ✅ **Use automatic logging** for: Product, Order, User, Category, Brand
2. ✅ **Keep manual logging** for: SAP sync, payments, webhooks, inventory ops
3. ✅ **Add activity log UI** to admin panel
4. ✅ **Clean old logs** regularly (keep 1 year)
5. ✅ **Monitor performance** and adjust as needed

This hybrid approach gives you:

- Complete audit trail for critical data
- Flexibility for custom business events
- Good performance
- Easy debugging and compliance
