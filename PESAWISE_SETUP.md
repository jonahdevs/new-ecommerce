# Pesawise Payment Integration

Complete guide for integrating Pesawise payment gateway with webhook support and iframe embedding.

## Overview

This integration supports:

- ✅ Webhook notifications (POST) for payment status
- ✅ User redirects (GET) after payment
- ✅ Iframe embedding for seamless checkout
- ✅ Full payment tracking and order management

## Configuration

### 1. Environment Variables

Add to your `.env`:

```env
PESAWISE_API_URL=https://api.pesawise.xyz/api
PESAWISE_API_KEY=your-api-key
PESAWISE_API_SECRET=your-api-secret
PESAWISE_BALANCE_ID_KES=your-balance-id
```

### 2. CSRF Protection

Already configured in `bootstrap/app.php`:

```php
$middleware->validateCsrfTokens(except: [
    'payment/callback/*',  // Allows Pesawise webhooks
]);
```

## How It Works

### Payment Flow

1. **User initiates checkout** → Order created
2. **System calls Pesawise API** → Payment URL generated
3. **User pays** (iframe or redirect)
4. **Pesawise sends POST webhook** → Order updated to "paid"
5. **User clicks "Continue"** → Pesawise sends GET redirect → Success page

### Webhook Handling

The same URL receives both:

- **POST**: Webhook with payment data (processed first)
- **GET**: User redirect after clicking "Continue"

```php
// PaymentCallbackController automatically detects request type
if ($request->isMethod('post')) {
    // Process webhook, update order
} else {
    // Redirect user to success page
}
```

## Iframe Implementation

Instead of redirecting to Pesawise, the payment page is embedded in an iframe for a seamless experience.

### Benefits

- ✅ User stays on your site
- ✅ Better UX (no redirect)
- ✅ Maintains your branding
- ✅ Easier to track user behavior
- ✅ Automatic payment status detection

### How It Works

1. **User clicks "Proceed to Checkout"**
    - Order is created
    - Pesawise payment URL is generated
    - User is redirected to `/checkout/payment`

2. **Payment Page (`/checkout/payment`)**
    - Displays Pesawise payment form in an iframe
    - Shows security notice and order reference
    - Polls payment status every 3 seconds
    - Listens for postMessage from iframe

3. **Payment Completion**
    - Pesawise sends POST webhook → Order updated
    - Status polling detects payment completion
    - User automatically redirected to success page

### Security

The iframe uses sandbox attributes:

- `allow-same-origin` - Required for Pesawise
- `allow-scripts` - Payment form scripts
- `allow-forms` - Form submission
- `allow-popups` - 3D Secure/OTP popups
- `allow-top-navigation` - Payment redirects

## Local Development

### Using ngrok

Pesawise needs a public URL to send webhooks:

```bash
# 1. Start ngrok
ngrok http https://your-local-domain.test

# 2. Update .env
APP_URL=https://your-ngrok-url.ngrok-free.dev

# 3. Clear cache
php artisan config:clear

# 4. Test payment
```

### Monitoring

Watch logs in real-time:

```bash
tail -f storage/logs/laravel.log
```

Look for:

- `=== PAYMENT CALLBACK RECEIVED ===` (both POST and GET)
- `=== POST WEBHOOK PROCESSING ===` (webhook)
- `Payment processed successfully via webhook`

## Iframe Implementation

Instead of redirecting to Pesawise, embed the payment page in an iframe for a seamless experience.

### Benefits

- User stays on your site
- Better UX (no redirect)
- Maintains your branding
- Easier to track user behavior

### Implementation

See the checkout page for iframe implementation details.

## Testing

### Manual Test

```bash
# Test POST webhook
curl -X POST http://your-domain.com/payment/callback/success \
  -H "Content-Type: application/json" \
  -d '{
    "status": "SUCCESS",
    "orderId": "test-123",
    "externalId": "ORD-TEST-001"
  }'
```

Expected: `{"status":"received","message":"Webhook processed successfully"}`

### Verify Database

```bash
# Check order status
php artisan tinker --execute="echo App\Models\Order::where('reference', 'YOUR-REF')->first(['status'])->toJson();"

# Check payment
php artisan tinker --execute="echo App\Models\Payment::whereHas('order', fn(\$q) => \$q->where('reference', 'YOUR-REF'))->first(['status'])->toJson();"
```

## Troubleshooting

### Webhook not received?

- Check ngrok is running
- Verify APP_URL in .env
- Check ngrok dashboard: http://127.0.0.1:4040
- Review Laravel logs

### 419 CSRF Error?

```bash
php artisan config:clear
php artisan cache:clear
```

### Order not updating?

- Verify `externalId` matches order reference
- Check logs for errors
- Ensure order exists in database

## Production Deployment

Before going live:

- [ ] Set APP_URL to production domain
- [ ] Test with Pesawise sandbox
- [ ] Monitor first transactions
- [ ] Set up error alerts
- [ ] Configure proper logging

## Key Files

- `bootstrap/app.php` - CSRF exclusion
- `app/Http/Controllers/PaymentCallbackController.php` - Webhook handler
- `app/Services/PesawiseService.php` - Payment initiation
- `routes/web.php` - Callback routes

## Support

For issues:

1. Check `storage/logs/laravel.log`
2. Verify Pesawise dashboard
3. Test webhook endpoint manually
4. Contact Pesawise support with `requestId`

---

**Status**: ✅ Production Ready  
**Last Updated**: 2026-02-17
