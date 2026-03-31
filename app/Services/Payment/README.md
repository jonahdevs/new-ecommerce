# Payment Service — Developer Reference

## Overview

All payment operations flow through `PaymentService` — the single entry point. `CheckoutService` never calls a gateway directly.

```
CheckoutService
      ↓
PaymentService::initiate()
      ↓
Gateway resolution (based on PaymentSettings)
      ↓
┌──────────┬──────────┬──────────┬──────────┬──────────┐
│ Custom   │ Pesawise │ Pesapal  │  M-Pesa  │  Stripe  │
└──────────┴──────────┴──────────┴──────────┴──────────┘
      ↓
PaymentResponse → Checkout UI
      ↓
Webhook → Gateway::handleWebhook() → Order confirmed
```

---

## Files

| File                               | Responsibility                                                           |
| ---------------------------------- | ------------------------------------------------------------------------ |
| `PaymentService.php`               | Gateway resolution + single entry point                                  |
| `Contracts/PaymentGateway.php`     | Interface all gateways must implement                                    |
| `Gateways/PesawiseGateway.php`     | Pesawise aggregator (redirect/iframe)                                    |
| `Gateways/MpesaGateway.php`        | M-Pesa STK push                                                          |
| `Gateways/StripeGateway.php`       | Stripe card payments                                                     |
| `Gateways/CustomGateway.php`       | Individual mode — delegates to M-Pesa or Stripe based on customer choice |
| `ValueObjects/PaymentResponse.php` | Return value from `initiate()`                                           |
| `ValueObjects/PaymentStatus.php`   | Return value from `verify()`                                             |

---

## Gateway Modes

Configured in `Settings → Payments → Gateways` and stored in `PaymentSettings`.

### `aggregator` mode

Uses a single aggregator gateway for all payments.

- `active_aggregator = pesawise` → `PesawiseGateway`
- `active_aggregator = pesapal` → `PesapalGateway` _(not yet implemented, falls back to Pesawise)_

### `individual` mode

Customer chooses their payment method at checkout.

- Resolves to `CustomGateway` which delegates to `MpesaGateway` or `StripeGateway` based on `CheckoutSession::getPaymentMethod()`

---

## PaymentResponse Types

Every `initiate()` call returns a `PaymentResponse` telling the UI what to do:

| Type       | Gateway                   | UI Action                                  |
| ---------- | ------------------------- | ------------------------------------------ |
| `redirect` | Pesawise, Pesapal, PayPal | Redirect customer to `$url`                |
| `iframe`   | Pesawise (iframe mode)    | Show `$url` in modal/iframe                |
| `stk_push` | M-Pesa                    | Show waiting screen, poll for confirmation |
| `inline`   | Stripe                    | Show card form with `$clientSecret`        |
| `failed`   | Any                       | Show error message                         |

---

## Gateway Contract

All gateways implement `PaymentGateway`:

```php
interface PaymentGateway
{
    public function initiate(Order $order, Payment $payment): PaymentResponse;
    public function verify(string $reference): PaymentStatus;
    public function handleWebhook(Request $request): void;
}
```

`handleWebhook()` is responsible for:

1. Validating the webhook signature
2. Updating `Payment` status
3. Updating `Order` status → `confirmed`
4. Deducting inventory (`InventoryService::deductStock()`)
5. Clearing the cart
6. Dispatching `SyncOrderToSapJob`
7. Sending order confirmation email

---

## Webhook Endpoints

| Gateway  | Endpoint                      | Route name              |
| -------- | ----------------------------- | ----------------------- |
| M-Pesa   | `POST /api/webhooks/mpesa`    | `api.webhooks.mpesa`    |
| Pesawise | `POST /api/webhooks/pesawise` | `api.webhooks.pesawise` |
| Stripe   | `POST /api/webhooks/stripe`   | `api.webhooks.stripe`   |

All webhook routes are excluded from CSRF middleware.

---

## Adding a New Gateway

1. Create `Gateways/YourGateway.php` implementing `PaymentGateway`
2. Add a case to `PaymentService::gateway()`:
    ```php
    'yourgateway' => app(YourGateway::class),
    ```
3. Add a webhook route in `routes/api.php`
4. Create a webhook controller in `Http/Controllers/Webhooks/`
5. Add the gateway option to `PaymentSettings` and the settings UI

---

## Key env vars

```env
# M-Pesa
MPESA_CONSUMER_KEY=
MPESA_CONSUMER_SECRET=
MPESA_SHORTCODE=
MPESA_PASSKEY=
MPESA_CALLBACK_URL=

# Pesawise
PESAWISE_API_KEY=
PESAWISE_MERCHANT_ID=
PESAWISE_CALLBACK_URL=

# Stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```
