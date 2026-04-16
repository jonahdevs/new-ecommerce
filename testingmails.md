# Mail Preview URLs

All previews are only available in local environment (`APP_ENV=local`).

---

## Auth

| Email | URL |
|---|---|
| Email Verification | `https://sheffield_ecommerce.test/dev/mail/verify-email` |
| Password Reset | `https://sheffield_ecommerce.test/dev/mail/password-reset` |

## Orders

| Email | URL |
|---|---|
| Order Confirmed (with KRA Invoice) | `https://sheffield_ecommerce.test/dev/mail/order-confirmation` |
| Order Status — Shipped | `https://sheffield_ecommerce.test/dev/mail/order-status/shipped` |
| Order Status — Delivered | `https://sheffield_ecommerce.test/dev/mail/order-status/delivered` |
| Order Status — Cancelled | `https://sheffield_ecommerce.test/dev/mail/order-status/cancelled` |

## Quotes

| Email | URL |
|---|---|
| Quote Sent to Customer | `https://sheffield_ecommerce.test/dev/mail/quote-sent` |
| Quote Expiring (2 days) | `https://sheffield_ecommerce.test/dev/mail/quote-expiring/2` |
| Quote Expiring (1 day) | `https://sheffield_ecommerce.test/dev/mail/quote-expiring/1` |

## PDFs

| Document | URL |
|---|---|
| Invoice PDF preview | `https://sheffield_ecommerce.test/dev/invoice-preview` |
| Quotation PDF preview | `https://sheffield_ecommerce.test/dev/quotation-preview` |

---

## Template Files

```
resources/views/mails/
├── auth/
│   ├── verify-email.blade.php
│   └── password-reset.blade.php
├── orders/
│   ├── confirmation.blade.php     ← order confirmation + tax invoice (single email)
│   └── status.blade.php
└── quotes/
    ├── sent.blade.php
    └── expiring.blade.php
```

## Mailable Classes

```
app/Mail/
├── PasswordResetMail.php
└── EmailVerificationMail.php
```

Notifications that use custom Blade views directly via `MailMessage::view()`:
- `OrderStatusNotification` → `mails/orders/status.blade.php`
- `OrderConfirmedNotification` → `mails/orders/confirmation.blade.php` (order confirmation + tax invoice)
- `QuoteSentNotification` → `mails/quotes/sent.blade.php`
- `QuoteExpiringNotification` → `mails/quotes/expiring.blade.php`

---

## Removed Emails

The following were removed to reduce email noise (one email per event):

| Removed | Reason |
|---|---|
| ~~Welcome Email~~ | Verification email already welcomes the user |
| ~~Order Confirmation Email~~ | KRA receipt email (arrives ~4 min later) now includes full order details + invoice PDF |
