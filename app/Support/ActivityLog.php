<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryPromotion;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\Review;
use App\Models\ShippingMethod;
use App\Models\TaxClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Central registry for the activity-log areas. Maps each Spatie `log_name`
 * to its display metadata and owning model, so the global audit pages and the
 * per-record history page share a single source of truth.
 */
class ActivityLog
{
    /**
     * @return array<string, array{label: string, icon: string, model: class-string<Model>, subjectKey: ?string}>
     */
    public static function logs(): array
    {
        return [
            // Tier 1
            'product' => ['label' => 'Products', 'icon' => 'cube', 'model' => Product::class, 'subjectKey' => 'name'],
            'product_variant' => ['label' => 'Product Variants', 'icon' => 'squares-2x2', 'model' => ProductVariant::class, 'subjectKey' => 'sku'],
            'order' => ['label' => 'Orders', 'icon' => 'shopping-bag', 'model' => Order::class, 'subjectKey' => 'order_number'],
            'payment' => ['label' => 'Payments', 'icon' => 'credit-card', 'model' => Payment::class, 'subjectKey' => null],
            'quote' => ['label' => 'Quotes', 'icon' => 'document-text', 'model' => Quote::class, 'subjectKey' => 'quote_number'],
            // Tier 2
            'tax_class' => ['label' => 'Tax Classes', 'icon' => 'calculator', 'model' => TaxClass::class, 'subjectKey' => 'name'],
            'delivery_promotion' => ['label' => 'Delivery Promotions', 'icon' => 'tag', 'model' => DeliveryPromotion::class, 'subjectKey' => 'name'],
            'shipping_method' => ['label' => 'Shipping Methods', 'icon' => 'truck', 'model' => ShippingMethod::class, 'subjectKey' => 'name'],
            'delivery_zone' => ['label' => 'Delivery Zones', 'icon' => 'map-pin', 'model' => DeliveryZone::class, 'subjectKey' => 'name'],
            // Tier 3
            'user' => ['label' => 'Users', 'icon' => 'user', 'model' => User::class, 'subjectKey' => 'name'],
            'category' => ['label' => 'Categories', 'icon' => 'folder', 'model' => Category::class, 'subjectKey' => 'name'],
            'brand' => ['label' => 'Brands', 'icon' => 'bookmark', 'model' => Brand::class, 'subjectKey' => 'name'],
            'review' => ['label' => 'Reviews', 'icon' => 'star', 'model' => Review::class, 'subjectKey' => null],
            'page' => ['label' => 'Pages', 'icon' => 'document', 'model' => Page::class, 'subjectKey' => 'title'],
        ];
    }

    /** @return array{label: string, icon: string, model: class-string<Model>, subjectKey: ?string}|null */
    public static function metaFor(string $logName): ?array
    {
        return self::logs()[$logName] ?? null;
    }

    public static function exists(string $logName): bool
    {
        return array_key_exists($logName, self::logs());
    }

    /**
     * Human label for a logged record, falling back to its id.
     */
    public static function subjectLabel(string $logName, ?Model $subject, int|string|null $subjectId = null): string
    {
        $key = self::logs()[$logName]['subjectKey'] ?? null;

        if ($key && $subject) {
            return (string) ($subject->{$key} ?? '#'.$subject->getKey());
        }

        return '#'.($subject?->getKey() ?? $subjectId);
    }

    /**
     * Logged fields holding integer-cents money amounts.
     *
     * @var list<string>
     */
    private const MONEY_FIELDS = [
        'price', 'sale_price', 'cost_price', 'compare_at_price',
        'amount_cents', 'total_cents', 'value_cents',
    ];

    /**
     * Humanise a raw logged value for display: money fields become formatted
     * currency, enums become their labels, dates and booleans are readable.
     */
    public static function formatValue(string $logName, string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (in_array($field, self::MONEY_FIELDS, true) && is_numeric($value)) {
            return app(Money::class)->format((int) $value);
        }

        $cast = (new (self::logs()[$logName]['model']))->getCasts()[$field] ?? null;

        if ($cast !== null) {
            if (enum_exists($cast)) {
                $enum = $cast::tryFrom($value);

                if ($enum !== null) {
                    return method_exists($enum, 'label') ? $enum->label() : $enum->name;
                }
            }

            if (in_array($cast, ['datetime', 'immutable_datetime', 'date', 'immutable_date'], true) || str_starts_with($cast, 'datetime:')) {
                return Carbon::parse($value)->format('d M Y, H:i');
            }

            if (in_array($cast, ['boolean', 'bool'], true)) {
                return $value ? 'Yes' : 'No';
            }
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }

    /**
     * Link to the subject's own admin screen, when one exists.
     */
    public static function subjectRoute(string $logName, ?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        return match ($logName) {
            'product' => route('admin.products.edit', $subject),
            'product_variant' => $subject->product_id ? route('admin.products.edit', $subject->product_id) : null,
            'order' => route('admin.orders.show', $subject),
            'payment' => route('admin.payments.show', $subject),
            'quote' => route('admin.quotes.show', $subject),
            default => null,
        };
    }
}
