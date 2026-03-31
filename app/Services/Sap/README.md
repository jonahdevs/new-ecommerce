# SAP Business One Integration

## Overview

Bidirectional integration with SAP Business One via a middleware API for order sync and KRA eTIMS compliance.

```
Order Paid → SyncOrderToSapJob → SAP Middleware → CU Number → KRA Receipt → Customer
```

---

## Files

| File                              | Responsibility                                    |
| --------------------------------- | ------------------------------------------------- |
| `SapIntegrationService.php`       | Builds and sends order payload to SAP middleware  |
| `SapWebhookHandler.php`           | Receives and processes inbound CU number webhooks |
| `SapWebhookController.php`        | HTTP entry point for inbound webhook              |
| `KraReceiptService.php`           | Generates and emails KRA-compliant receipt        |
| `SapApiException.php`             | Exception for non-2xx SAP API responses           |
| `ValueObjects/SapSyncResult.php`  | Return value from `syncOrder()`                   |
| `ValueObjects/CuNumberResult.php` | Return value from `pollCuNumber()`                |
| `Jobs/SyncOrderToSapJob.php`      | Queued job — dispatched after payment confirmed   |
| `Jobs/PollSapCuNumberJob.php`     | Polling fallback if webhook doesn't arrive        |

---

## Status Flow

```
pending → syncing → cu_pending → cu_received
                        ↓
                     failed  (after 3 retries → admin notified)
```

| Status        | Meaning                                    |
| ------------- | ------------------------------------------ |
| `pending`     | Order paid, job queued                     |
| `syncing`     | API call in progress                       |
| `cu_pending`  | Invoice created in SAP, awaiting CU number |
| `cu_received` | CU number stored, receipt sent to customer |
| `failed`      | All retries exhausted                      |

---

## Outbound — System → SAP

**Endpoint**: `POST {SAP_BASE_URL}/api/invoice/create`

**Payload**:

```json
{
    "credit_guard_response": {
        "authNumber": "123456",
        "cardBrand": "Visa",
        "cardNo": "4242",
        "uid": "txn_123456789",
        "creditCardToken": "tok_abc123"
    },
    "customer": {
        "email": "customer@example.com",
        "full_name": "John Doe",
        "full_address": "123 Main St, Westlands, Nairobi",
        "mobile_phone": "+254712345678",
        "note": "Deliver before 5 PM",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-03-31T14:30:00.000000Z"
    },
    "order": {
        "Orderid": 789,
        "name": "John Doe",
        "phone": "+254712345678",
        "payment_status": "Paid",
        "cart": {
            "debit_total_price": 15750.0,
            "lines": [
                {
                    "code": "SKU-001",
                    "item_id": 123,
                    "line_item_id": 456,
                    "price": 5000.0,
                    "quantity": 2,
                    "linetotal": 10000.0
                }
            ]
        }
    }
}
```

**Expected response**:

```json
{
    "success": true,
    "invoice_number": "INV-2024-001234",
    "Orderid": "789"
}
```

---

## Inbound Webhook — SAP → System

**Endpoint**: `POST /api/webhooks/sap`

**Security**: HMAC-SHA256 signature in `X-SAP-Signature` header

```php
$signature = hash_hmac('sha256', $rawBody, config('sap.webhook_secret'));
```

**Payload**:

```json
{
    "event": "invoice.cu_number_generated",
    "data": {
        "external_reference": "ORD-2024-001234",
        "cu_number": "CU123456789012345678",
        "kra_invoice_number": "INV-KRA-2024-001",
        "validated_at": "2024-03-31T14:30:00Z"
    }
}
```

**Response codes**:

- `200` — success
- `401` — invalid signature
- `500` — processing error (SAP will retry)

The handler is **idempotent** — duplicate CU numbers for the same order are ignored.

---

## CU Number Delivery

Two methods run in parallel — whichever arrives first wins:

1. **Webhook** (preferred) — SAP pushes CU number immediately after KRA validation
2. **Polling** (`PollSapCuNumberJob`) — queries SAP every 30s for up to 10 minutes as fallback

Once received, `KraReceiptService` generates a PDF receipt and emails it to the customer.

---

## Configuration

```env
SAP_BASE_URL=https://sap-middleware.yourdomain.com
SAP_WEBHOOK_SECRET=your-shared-secret
```

Config file: `config/sap.php`

### Generating the Webhook Secret

Generate a cryptographically secure secret and share it with the SAP middleware team. They configure it on their end to sign outbound webhook requests.

**Option 1 — PHP one-liner (recommended):**

```bash
php -r "echo bin2hex(random_bytes(32));"
```

**Option 2 — OpenSSL:**

```bash
openssl rand -hex 32
```

**Option 3 — Laravel Artisan interactive:**

```bash
php artisan tinker
>>> echo bin2hex(random_bytes(32));
```

> Note: avoid `php artisan tinker --execute` — it skips HTTP bootstrapping and throws a `UrlGenerator` null request error.

All options produce a 64-character hex string, e.g.:

```
a3f8c2d1e4b5a6f7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1
```

Add it to `.env`:

```env
SAP_WEBHOOK_SECRET=a3f8c2d1e4b5a6f7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1
```

> **Important**: Never commit the secret to version control. Rotate it by generating a new value, updating both `.env` and the SAP middleware config simultaneously to avoid a gap in webhook delivery.

---

## Audit Logs

Every outbound call and inbound webhook is recorded in `sap_sync_logs`:

```sql
SELECT * FROM sap_sync_logs WHERE order_id = 123 ORDER BY created_at DESC;
```

Activity log events: `sap_sync_success`, `sap_sync_failed`

---

## Testing the Webhook Locally

```bash
SECRET="your-webhook-secret"
PAYLOAD='{"event":"invoice.cu_number_generated","data":{"external_reference":"ORD-001","cu_number":"CU123456789012345678","kra_invoice_number":"INV-KRA-001","validated_at":"2024-03-31T14:30:00Z"}}'
SIG=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')

curl -X POST http://localhost/api/webhooks/sap \
  -H "Content-Type: application/json" \
  -H "X-SAP-Signature: $SIG" \
  -d "$PAYLOAD"
```
