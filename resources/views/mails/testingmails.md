# Mail Preview URLs

All previews are only available in local environment (`APP_ENV=local`).

---

## Auth

| Email | URL |
|---|---|
| Welcome | `https://sheffield_ecommerce.test/dev/mail/welcome` |
| Email Verification | `https://sheffield_ecommerce.test/dev/mail/verify-email` |
| Password Reset | `https://sheffield_ecommerce.test/dev/mail/password-reset` |

## Orders

| Email | URL |
|---|---|
| Order Confirmation | `https://sheffield_ecommerce.test/dev/mail/order-confirmation` |
| Order Status — Confirmed | `https://sheffield_ecommerce.test/dev/mail/order-status/confirmed` |
| Order Status — Processing | `https://sheffield_ecommerce.test/dev/mail/order-status/processing` |
| Order Status — Shipped | `https://sheffield_ecommerce.test/dev/mail/order-status/shipped` |
| Order Status — Delivered | `https://sheffield_ecommerce.test/dev/mail/order-status/delivered` |
| Order Status — Cancelled | `https://sheffield_ecommerce.test/dev/mail/order-status/cancelled` |
| KRA Tax Invoice | `https://sheffield_ecommerce.test/dev/mail/kra-receipt` |

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
│   ├── welcome.blade.php
│   ├── verify-email.blade.php
│   └── password-reset.blade.php
├── orders/
│   ├── confirmation.blade.php
│   ├── status.blade.php
│   └── kra-receipt.blade.php
└── quotes/
    ├── sent.blade.php
    └── expiring.blade.php
```

## Mailable Classes

```
app/Mail/
├── WelcomeMail.php
├── OrderConfirmationMail.php
├── PasswordResetMail.php
└── EmailVerificationMail.php
```

Notifications that use custom Blade views directly via `MailMessage::view()`:
- `OrderStatusNotification` → `mails/orders/status.blade.php`
- `KraReceiptNotification` → `mails/orders/kra-receipt.blade.php`
- `QuoteSentNotification` → `mails/quotes/sent.blade.php`
- `QuoteExpiringNotification` → `mails/quotes/expiring.blade.php`
