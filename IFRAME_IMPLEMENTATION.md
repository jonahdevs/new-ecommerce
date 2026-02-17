# Pesawise Iframe Payment Implementation

## Overview

The payment flow now uses an iframe instead of redirecting users to Pesawise. This provides a seamless checkout experience while maintaining security.

## What Changed

### Before (Redirect)

```
User → Checkout → Redirect to Pesawise → Pay → Redirect back → Success
```

### After (Iframe)

```
User → Checkout → Payment Page (iframe) → Pay → Auto-detect → Success
```

## Implementation

### 1. New Payment Page

**File**: `resources/views/pages/checkout/payment.blade.php`

Features:

- Embeds Pesawise payment form in iframe
- Polls payment status every 3 seconds
- Listens for postMessage from iframe
- Shows security notice
- Cancel payment option
- Loading overlay on completion

### 2. Updated CheckoutService

**File**: `app/Services/CheckoutService.php`

Changes:

- Stores payment URL in session instead of redirecting
- Redirects to `/checkout/payment` route
- Maintains order reference for tracking

### 3. New Route

**File**: `routes/web.php`

```php
Route::livewire('/checkout/payment', 'pages::checkout.payment')
    ->name('checkout.payment');
```

## User Flow

1. **Checkout Summary**
    - User reviews order
    - Clicks "Proceed to Checkout"

2. **Order Creation**
    - System creates order
    - Reserves inventory
    - Generates Pesawise payment URL
    - Stores URL in session

3. **Payment Page**
    - User redirected to `/checkout/payment`
    - Pesawise form loads in iframe
    - Security notice displayed
    - Order reference shown

4. **Payment Process**
    - User enters payment details in iframe
    - Completes payment on Pesawise

5. **Webhook & Detection**
    - Pesawise sends POST webhook → Order updated to "paid"
    - Status polling detects payment completion
    - User automatically redirected to success page

## Technical Details

### Iframe Configuration

```html
<iframe
    src="{{ $paymentUrl }}"
    allow="payment"
    sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-top-navigation"
></iframe>
```

### Security Sandbox Attributes

- `allow-same-origin` - Required for Pesawise to function properly
- `allow-scripts` - Allows payment form JavaScript
- `allow-forms` - Allows form submission
- `allow-popups` - For 3D Secure/OTP verification popups
- `allow-top-navigation` - For payment flow redirects

### Payment Status Polling

```javascript
// Polls every 3 seconds
let statusCheckInterval = setInterval(() => {
    $wire.checkPaymentStatus();
}, 3000);
```

The polling checks if the order status changed to "processing" (which happens when the webhook is received).

### PostMessage Listener

```javascript
window.addEventListener("message", (event) => {
    // Verify origin
    if (event.origin !== "https://payments.pesawise.xyz") {
        return;
    }

    // Handle payment completion
    if (event.data.status === "success") {
        $wire.checkPaymentStatus();
    }
});
```

## Benefits

### User Experience

- ✅ No jarring redirects
- ✅ Stays on your site
- ✅ Maintains branding
- ✅ Faster perceived performance
- ✅ Automatic success detection

### Technical

- ✅ Better error handling
- ✅ Easier to track analytics
- ✅ Can show custom loading states
- ✅ Cancel payment option
- ✅ Session management

### Security

- ✅ Iframe sandbox protection
- ✅ Origin verification
- ✅ Secure payment processing by Pesawise
- ✅ No payment data touches your server

## Customization

### Change Polling Interval

```javascript
// In payment.blade.php @script section
let statusCheckInterval = setInterval(() => {
    $wire.checkPaymentStatus();
}, 5000); // 5 seconds instead of 3
```

### Customize Iframe Height

```html
<!-- Adjust padding-bottom percentage -->
<div class="relative" style="padding-bottom: 80%; min-height: 700px;">
    <iframe ...></iframe>
</div>
```

### Add Custom Branding

```blade
{{-- Add your logo --}}
<div class="flex items-center gap-3">
    <x-app-logo class="h-8" />
    <div class="h-6 w-px bg-zinc-300"></div>
    <flux:heading>Secure Payment</flux:heading>
</div>
```

## Testing

### Local Testing

1. Start ngrok:

```bash
ngrok http https://your-local-domain.test
```

2. Update .env:

```env
APP_URL=https://your-ngrok-url.ngrok-free.dev
```

3. Clear cache:

```bash
php artisan config:clear
```

4. Test checkout flow

### What to Check

- [ ] Iframe loads Pesawise payment form
- [ ] Security notice displays
- [ ] Order reference shows correctly
- [ ] Cancel button works
- [ ] Payment completes successfully
- [ ] Webhook received (check logs)
- [ ] Status polling detects completion
- [ ] Auto-redirect to success page
- [ ] Loading overlay shows

## Troubleshooting

### Iframe not loading?

Check browser console for errors. Common issues:

- CORS policy (should be fine with Pesawise)
- X-Frame-Options blocking (Pesawise should allow)
- Network connectivity

### Payment not detected?

- Check webhook is being received (logs)
- Verify polling is running (browser console)
- Check order status in database
- Ensure session data is present

### Cancel button not working?

- Check session is being cleared
- Verify redirect route exists
- Check for JavaScript errors

## Files Modified

1. ✅ `resources/views/pages/checkout/payment.blade.php` - New payment page
2. ✅ `app/Services/CheckoutService.php` - Updated to use iframe
3. ✅ `routes/web.php` - Added payment route
4. ✅ `PESAWISE_SETUP.md` - Updated documentation

## Next Steps

1. Test the complete flow
2. Customize branding if needed
3. Adjust polling interval if desired
4. Add analytics tracking
5. Deploy to production

---

**Status**: ✅ Implemented  
**Last Updated**: 2026-02-17
