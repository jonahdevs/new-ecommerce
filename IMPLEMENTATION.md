# Sheffield Africa — Implementation Reference

## 1. Activity Logging (spatie/laravel-activitylog)

**Package**: `spatie/laravel-activitylog`

**Logged events** (via services):

- `OrderService` — `order_placed`, `order_status_changed`, `order_cancelled`
- `InventoryService` — `inventory_deducted`, `inventory_reserved`
- `Payment gateways` — `payment_confirmed`, `payment_failed`
- `MpesaWebhookController` — `mpesa_webhook_received`

**Admin UI**:

- Dashboard widget: `livewire:admin.recent-activity-widget` (last 15 events)
- Full log page: `/admin/activity-logs` → `admin.activity-logs.index`
- Sidebar: Reports & Analytics → Activity Logs

**Event types for filtering**: `orders`, `payments`, `inventory`, `sap`, `quotes`, `users`

---

## 2. Backup System (spatie/laravel-backup)

**Package**: `spatie/laravel-backup`

**Schedule** (in `routes/console.php`):

- Daily DB backup at 2:00 AM
- Weekly full backup Sunday at 3:00 AM
- Monthly cleanup on the 1st at 4:00 AM

**Commands**:

```bash
php artisan backup:run              # Full backup
php artisan backup:run --only-db    # DB only
php artisan backup:list             # List backups
php artisan backup:clean            # Apply retention policy
php artisan backup:monitor          # Health check
```

**Key env vars**:

```env
BACKUP_DISK=local
BACKUP_NOTIFICATION_EMAIL=admin@example.com
MYSQLDUMP_PATH=C:/xampp/mysql/bin/mysqldump.exe   # Windows only
```

---

## 3. SEO (artesaos/seotools)

**Package**: `artesaos/seotools` v1.4.1

**Head partial**: `resources/views/partials/head.blade.php` — renders `{!! SEO::generate() !!}`

**Public pages with full SEO**:
| Page | File |
|------|------|
| Home | `pages/home/index.blade.php` |
| Shop | `pages/shop.blade.php` |
| Product | `pages/product-details/index.blade.php` |
| Category | `pages/category-products.blade.php` |

**Private pages (noindex, nofollow)**:

- Cart, Wishlist, Quote
- All 6 checkout pages
- All 4 customer account pages

**Config**: `config/seotools.php`

---

## 4. SAP Business One Integration

See [`app/Services/Sap/README.md`](app/Services/Sap/README.md) for full integration details, payload formats, and webhook setup.

**Key env vars**:

```env
SAP_BASE_URL=https://sap-middleware.yourdomain.com
SAP_WEBHOOK_SECRET=your-shared-secret
```

---

## 5. Admin UI Patterns

### Navigation

- **Sidebar**: Single entry per section (e.g. "Logistics" → overview)
- **Settings & Logistics**: Top tabs + left sub-nav layout (`resources/views/pages/admin/logistics/layout.blade.php`, `settings/layout.blade.php`)

### Tab Style (consistent across all pages)

```html
<div class="mt-4 border-b border-zinc-200 dark:border-zinc-600">
    <nav class="flex gap-1 overflow-x-auto">
        <button @class([
            'inline-flex items-center gap-1.5 px-3 py-2 text-sm whitespace-nowrap transition-colors duration-150',
            'bg-brand-primary text-brand-primary-content font-medium' => $active,
            'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800' => !$active,
        ])>Tab Label</button>
    </nav>
</div>
```

### Table Card Pattern

```html
<flux:card
    class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800"
>
    <div
        class="flex items-center gap-4 px-5 py-3 border-b dark:border-zinc-600"
    >
        {{-- filters --}}
    </div>
    <flux:table :paginate="$this->items"> ... </flux:table>
</flux:card>
```

### Pagination default: `->paginate(10)` on all tables

---

## 6. Order Item Snapshot

See [`app/Services/CheckoutService.md`](app/Services/CheckoutService.md) for full checkout flow, session management, and snapshot structure.

`OrderItem->product_snapshot` stores product data at time of purchase:

```php
[
    'name'       => $product->name,
    'sku'        => $variant?->sku ?? $product->sku,
    'image_path' => $product->image_path,
    'price'      => $originalPrice,
    'final_price'=> $unitPrice,
    'weight_kg'  => $product->weight ?? 0.5,
]
```

Helper methods on `OrderItem`: `getProductName()`, `getProductSku()`, `getProductImagePath()`
