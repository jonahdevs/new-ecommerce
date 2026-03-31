# Checkout Service — Developer Reference

## Overview

`CheckoutService` owns the single checkout path for cart-based orders. It orchestrates pre-flight checks, order creation, stock reservation, and payment initiation — nothing else.

```
Customer submits checkout
        ↓
CheckoutService::initiateCheckout()
        ↓
┌─────────────────────────────────────┐
│ 1. Pre-flight checks                │
│    - Cart not empty                 │
│    - Address exists                 │
│    - Shipping selected              │
│    - Stock available                │
├─────────────────────────────────────┤
│ 2. Resume failed order? (M-Pesa     │
│    timeout retry — no duplicate)    │
├─────────────────────────────────────┤
│ 3. DB transaction                   │
│    - Create Order                   │
│    - Create OrderItems + snapshots  │
│    - Create Payment record          │
├─────────────────────────────────────┤
│ 4. Reserve stock (outside tx)       │
├─────────────────────────────────────┤
│ 5. Gateway call (outside tx)        │
│    → PaymentService::initiate()     │
│    → Returns PaymentResponse        │
└─────────────────────────────────────┘
        ↓
PaymentResponse → Livewire checkout component
```

---

## Responsibilities

| Does                      | Does NOT                |
| ------------------------- | ----------------------- |
| Create Order + OrderItems | Confirm payment         |
| Create Payment record     | Dispatch SAP sync       |
| Reserve stock             | Clear cart              |
| Initiate payment gateway  | Send confirmation email |
| Resume failed orders      | Handle webhooks         |

Payment confirmation, SAP sync, cart clearing, and email all fire inside the gateway's `handleWebhook()` / `handleSucceeded()` after payment is confirmed.

---

## Files

| File                      | Responsibility                                |
| ------------------------- | --------------------------------------------- |
| `CheckoutService.php`     | Main checkout orchestration                   |
| `CheckoutSession.php`     | Single source of truth for all checkout state |
| `OrderSummaryService.php` | Builds totals array for the order summary UI  |

---

## CheckoutSession

`CheckoutSession` is the only class that reads/writes `session('checkout.*')`. All checkout components go through it.

### Key methods

| Method                     | Notes                                                   |
| -------------------------- | ------------------------------------------------------- |
| `setAddressId(int)`        | Called when customer selects address                    |
| `getAddressId(): ?int`     | Returns null if no address chosen                       |
| `setShipping(array)`       | Stores full method + cost + breakdown                   |
| `getShipping(): ?array`    | Full shipping data array                                |
| `hasShipping(): bool`      | Guards navigation to summary step                       |
| `isPus(): bool`            | True if selected method is pickup station               |
| `getShippingCost(): float` | Cost in KES                                             |
| `setPaymentMethod(string)` | `mpesa` or `card` (individual mode only)                |
| `isComplete(): bool`       | True when shipping is selected                          |
| `clear()`                  | **Must be called after order placed** — wipes all state |

### Session keys

```
checkout.address_id
checkout.shipping.method_id
checkout.shipping.method_name
checkout.shipping.method_code
checkout.shipping.method_type
checkout.shipping.cost
checkout.shipping.zone_id
checkout.shipping.rate_id
checkout.shipping.station_id
checkout.shipping.station_name
checkout.shipping.cost_breakdown
checkout.shipping.delivery_window
checkout.payment_method
```

---

## Order Item Snapshot

Each `OrderItem` stores a `product_snapshot` at the time of purchase — prices and product data are frozen so historical orders are never affected by future product changes.

```php
'product_snapshot' => [
    'id'          => $product->id,
    'name'        => $product->name,
    'sku'         => $variant?->sku ?? $product->sku,
    'slug'        => $product->slug,
    'image_path'  => $product->image_path,
    'price'       => $originalPrice,
    'sale_price'  => $product->sale_price,
    'final_price' => $unitPrice,
    'weight_kg'   => $product->weight ?? 0.5,
    'brand'       => $product->brand?->name,
    'variant'     => [...] // null if no variant
]
```

Helper methods on `OrderItem`: `getProductName()`, `getProductSku()`, `getProductImagePath()`

---

## Shipping Snapshot

The `shipping_snapshot` on `Order` freezes the shipping selection at checkout:

```php
'shipping_snapshot' => [
    'method_id'       => ...,
    'method_name'     => ...,
    'method_code'     => ...,
    'method_type'     => ...,   // flat | pus | distance
    'zone_id'         => ...,
    'rate_id'         => ...,
    'station_id'      => ...,   // PUS only
    'station_name'    => ...,   // PUS only
    'cost'            => ...,
    'cost_breakdown'  => [...],
    'delivery_window' => ...,
    'weight_kg'       => ...,
]
```

---

## Tax Calculation

Tax is calculated by `TaxService` based on `TaxSettings`:

- **Inclusive**: Tax is already in the price. Total = subtotal - discount + shipping (tax extracted for display only)
- **Exclusive**: Tax is added on top. Total = subtotal - discount + shipping + tax

---

## Failed Order Resume

To handle M-Pesa STK push timeouts (customer doesn't respond, tries again), `CheckoutService` looks for a recent `PENDING` + `FAILED` order within its expiry window before creating a new one. If found, it re-initiates payment on the existing order — no duplicate orders.

```php
$existingOrder = Order::where('user_id', $user->id)
    ->where('status', OrderStatus::PENDING)
    ->where('payment_status', PaymentStatus::FAILED)
    ->where('expires_at', '>', now())
    ->latest()
    ->first();
```

---

## OrderSummaryService

Builds the totals array consumed by the `checkout.summary` Livewire component.

```php
$summary = app(OrderSummaryService::class)->summary();
// Returns:
[
    'subtotal'          => float,
    'discount'          => float,
    'shipping_cost'     => float,
    'shipping_method'   => string|null,
    'shipping_window'   => string|null,
    'station_name'      => string|null,  // PUS only
    'tax'               => float,
    'tax_name'          => string,
    'tax_rate'          => string,
    'tax_enabled'       => bool,
    'tax_inclusive'     => bool,
    'total'             => float,
    'shipping_selected' => bool,
]
```
