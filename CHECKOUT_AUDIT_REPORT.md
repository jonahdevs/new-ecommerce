# Checkout Flow Audit Report

**Date:** March 29, 2026  
**Scope:** Cart → Order Confirmation (including quotation flow)

---

## Executive Summary

The checkout flow is well-architected with clear separation of concerns. The system supports two paths: standard cart checkout with payment, and quotation requests for areas outside delivery zones. However, several issues were identified that could cause bugs, data inconsistencies, or poor user experience.

---

## Flow Overview

```
Cart → Summary → Address Selection → Shipping → Payment Methods → Pay → Success
                                          ↓
                              (Quote flow) → Quote Success
```

**Key Services:**
- `CartService` - Cart management
- `CheckoutSession` - Session state management
- `CheckoutService` - Order creation
- `PaymentService` - Payment gateway abstraction
- `OrderSummaryService` - Summary calculations
- `InventoryService` - Stock management
- `QuotationService` - Quote workflow

---

## Critical Issues

### 1. Address Session Key Inconsistency
**Location:** `address/index.blade.php` vs `CheckoutSession.php`  
**Severity:** HIGH

The address index page stores the selected address in `session('checkout_address_id')`:
```php
session(['checkout_address_id' => $this->selectedAddress]);
```

But `CheckoutSession` uses a different key:
```php
private const KEY_ADDRESS = 'checkout.address_id';
```

**Impact:** Selected address from the address picker is never read by the checkout flow. The system falls back to the default address.

**Fix:** Update `address/index.blade.php` to use `CheckoutSession::setAddressId()`:
```php
app(CheckoutSession::class)->setAddressId($this->selectedAddress);
```

---

### 2. Stock Deducted Before Payment Confirmation
**Location:** `CheckoutService.php` lines 130-145  
**Severity:** HIGH

Stock is decremented inside the order creation transaction, before payment is confirmed:
```php
$product->decrement('stock_quantity', $item->quantity);
```

If payment fails or is abandoned, stock is restored only if `PaymentResponse::isFailed()` returns true. However:
- If the user closes the browser during payment, stock remains deducted
- The `expires_at` field is set but there's no scheduled job to restore stock for expired orders

**Impact:** Phantom stock depletion over time.

**Fix Options:**
1. Use `InventoryService::reserveStock()` instead of direct decrement (reservation pattern already exists)
2. Add a scheduled job to restore stock for expired pending orders
3. Only deduct stock on payment confirmation (in webhook handler)

---

### 3. InventoryService Not Used in CheckoutService
**Location:** `CheckoutService.php`  
**Severity:** MEDIUM

`InventoryService` has well-designed methods for stock management:
- `checkAvailability()` - Pre-flight stock check
- `reserveStock()` - Soft lock with expiry
- `deductStock()` - Final deduction after payment
- `releaseReservation()` - Cleanup on cancellation

But `CheckoutService` doesn't use any of these. It implements its own inline stock logic.

**Impact:** Code duplication, inconsistent stock handling, reservation system unused.

**Fix:** Refactor `CheckoutService` to use `InventoryService`:
```php
// Before order creation
$unavailable = $this->inventoryService->checkAvailability($cart);
if (!empty($unavailable)) {
    throw new \RuntimeException('Some items are out of stock');
}

// After order creation (inside transaction)
$this->inventoryService->reserveStock($order);

// In webhook handler (on payment success)
$this->inventoryService->deductStock($order);
```

---

### 4. Cancel Page is Empty
**Location:** `resources/views/pages/checkout/cancel.blade.php`  
**Severity:** MEDIUM

The cancel page has no implementation:
```php
new class extends Component {
    //
};
```

**Impact:** Users redirected here after payment cancellation see a blank page.

**Fix:** Implement proper cancellation handling with:
- Clear message about what happened
- Option to retry payment
- Link back to cart or orders

---

### 5. Quote Flow Missing Payment Method Guard
**Location:** `summary.blade.php` mount()  
**Severity:** LOW

The summary page redirects to payment-methods if no payment method is selected, but only for non-quote flows:
```php
if (app(PaymentService::class)->isCustom() && !$checkoutSession->hasPaymentMethod() 
    && $checkoutSession->getShipping()['method_type'] !== 'quote') {
```

This is correct, but the condition is complex and could be clearer.

---

### 6. Variant Price Not Used in CheckoutService
**Location:** `CheckoutService.php` line 119  
**Severity:** MEDIUM

Order items are created using product price, ignoring variant price:
```php
'unit_price_cents' => (int) round($item->product->final_price * 100),
```

But `CartService::summary()` correctly uses variant price:
```php
$price = $item->variant?->final_price ?? $item->product->final_price;
```

**Impact:** Orders with variants may have incorrect pricing.

**Fix:**
```php
$unitPrice = $item->variant?->final_price ?? $item->product->final_price;
'unit_price_cents' => (int) round($unitPrice * 100),
```

---

### 7. Missing Variant in Product Snapshot
**Location:** `CheckoutService.php` lines 121-132  
**Severity:** MEDIUM

The product snapshot doesn't include variant information:
```php
'product_snapshot' => [
    'id'          => $item->product->id,
    'name'        => $item->product->name,
    // ... no variant data
],
```

But the cart page displays variant attributes. This data should be preserved for order history.

**Fix:** Add variant snapshot:
```php
'variant_snapshot' => $item->variant ? [
    'id' => $item->variant->id,
    'sku' => $item->variant->sku,
    'attributes' => $item->variant->attributeValues->mapWithKeys(
        fn($av) => [$av->attribute->name => $av->label ?: $av->value]
    )->toArray(),
] : null,
```

---

### 8. PesawiseGateway Stock Restoration Doesn't Use InventoryService
**Location:** `PesawiseGateway.php` `restoreStock()` method  
**Severity:** LOW

```php
private function restoreStock(Order $order): void
{
    foreach ($order->items()->with('product')->get() as $item) {
        $item->product?->increment('stock_quantity', $item->quantity);
    }
}
```

This bypasses `InventoryService::releaseReservation()` and doesn't handle variants.

---

### 9. Success Page Session Expiry Logic
**Location:** `success.blade.php`  
**Severity:** LOW

The success page checks session expiry:
```php
if (!$expiresAt || now()->timestamp > $expiresAt) {
    session()->forget(['payment_success_order_id', 'payment_success_expires_at']);
    return redirect()->route('customer.orders.index');
}
```

But `payment_success_expires_at` is never set anywhere in the codebase.

**Impact:** Users may be incorrectly redirected away from success page.

**Fix:** Set the session values in the payment callback handler:
```php
session([
    'payment_success_order_id' => $order->id,
    'payment_success_expires_at' => now()->addMinutes(30)->timestamp,
]);
```

---

### 10. ~~Shipping Page Filters Out Quote Options~~ (RESOLVED)
**Location:** `shipping.blade.php` line 45  
**Status:** FIXED - Quote flow removed from cart checkout entirely. Users outside delivery zones will see the main store as a pickup station option.

---

## Minor Issues & Improvements

### 11. Hardcoded Currency
Multiple files hardcode 'KES':
- `CheckoutService.php`
- `QuotationService.php`
- `PesawiseGateway.php`

**Recommendation:** Use a config value or settings.

### 12. Missing Error Handling in Address Form
`CustomerAddressForm.php` doesn't validate that the selected county has a shipping zone.

### 13. Map Script Loaded from CDN
`_form-fields.blade.php` loads Leaflet from unpkg.com. Consider self-hosting for reliability.

### 14. Phone Validation Regex
The regex `^[0-9\s]{9,12}$` allows spaces but the mask is `999 999 999`. These should align.

### 15. Order Reference Format
`Order::generateReference()` uses `SO-YYYY-NNNNNN` but `QuotationService` uses `Order::generateReference('quotation')` which doesn't exist.

**Fix:** The method signature doesn't accept a prefix parameter. Either add it or use a separate method.

---

## Security Considerations

### 16. Address Ownership Check
`address/edit.blade.php` correctly checks ownership:
```php
abort_if($address->user_id !== auth()->id(), 403);
```

But `address/index.blade.php` doesn't verify the selected address belongs to the user before storing in session. The `selectAddress()` method does check, but the `$set` wire call doesn't.

### 17. Order Access on Pay Page
`pay.blade.php` correctly verifies order ownership:
```php
abort_if($orderModel->user_id !== auth()->id(), 403);
```

---

## Performance Considerations

### 18. N+1 Query in Cart Summary
`CartService::summary()` iterates over items and accesses `$item->variant` and `$item->product` without eager loading in the method itself.

### 19. Multiple Cart Queries
Several checkout pages call `app(CartService::class)->getCart()` multiple times. Consider caching within request lifecycle.

---

## Recommendations Summary

| Priority | Issue | Effort | Status |
|----------|-------|--------|--------|
| HIGH | Fix address session key inconsistency | Low | ✅ FIXED |
| HIGH | Implement stock reservation pattern | Medium | ✅ FIXED |
| MEDIUM | Use variant price in CheckoutService | Low | ✅ FIXED |
| MEDIUM | Add variant to product snapshot | Low | ✅ FIXED |
| MEDIUM | Implement cancel page | Low | ✅ FIXED |
| MEDIUM | Unify stock management via InventoryService | Medium | ✅ FIXED |
| LOW | Set success page session values | Low | N/A (uses confirmation page) |
| LOW | Add scheduled job for expired order cleanup | Medium | ✅ FIXED |
| LOW | Fix hardcoded currency | Low | ✅ FIXED |
| LOW | Address ownership check in index.blade.php | Low | ✅ FIXED |
| LOW | Phone validation regex alignment | Low | ✅ FIXED |
| LOW | Shipping zone validation in address form | Low | ✅ FIXED |
| LOW | N+1 query in CartService::summary() | Low | ✅ FIXED |
| LOW | Multiple cart queries optimization | Low | ✅ FIXED |
| LOW | Map script from CDN | Low | Deferred (reliability vs bundle size) |

---

## Files Reviewed

- `resources/views/pages/cart.blade.php`
- `resources/views/pages/checkout/summary.blade.php`
- `resources/views/pages/checkout/address/index.blade.php`
- `resources/views/pages/checkout/address/create.blade.php`
- `resources/views/pages/checkout/address/edit.blade.php`
- `resources/views/pages/checkout/address/_form-fields.blade.php`
- `resources/views/pages/checkout/shipping.blade.php`
- `resources/views/pages/checkout/payment-methods.blade.php`
- `resources/views/pages/checkout/pay.blade.php`
- `resources/views/pages/checkout/success.blade.php`
- `resources/views/pages/checkout/cancel.blade.php`
- `resources/views/pages/checkout/quote-success.blade.php`
- `app/Services/CartService.php`
- `app/Services/CheckoutService.php`
- `app/Services/CheckoutSession.php`
- `app/Services/OrderService.php`
- `app/Services/OrderSummaryService.php`
- `app/Services/InventoryService.php`
- `app/Services/QuotationService.php`
- `app/Services/Payment/PaymentService.php`
- `app/Services/Payment/Gateways/PesawiseGateway.php`
- `app/Services/Payment/Contracts/PaymentGateway.php`
- `app/Livewire/Forms/CustomerAddressForm.php`
- `app/Models/Order.php`
- `routes/web.php`
