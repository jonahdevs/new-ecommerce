# SAP Business One Integration

## Table of Contents

1. [Overview](#overview)
2. [Files](#files)
3. [Order Sync - Status Flow](#status-flow)
4. [Outbound — System → SAP (Order Sync)](#outbound--system--sap)
5. [Inbound Webhook — SAP → System](#inbound-webhook--sap--system)
6. [Product Sync — SAP → System (Price & Stock)](#product-sync--sap--system)
7. [Configuration](#configuration)
8. [Audit Logs](#audit-logs)
9. [Testing the Webhook Locally](#testing-the-webhook-locally)

---

## Overview

Bidirectional integration with SAP Business One via a middleware API for:

- Order sync to SAP and KRA eTIMS compliance
- Product price and stock updates from SAP

```
Order Paid → SyncOrderToSapJob → SAP Middleware → KRA Webhook (CU Number) → Receipt → Customer
SAP Products → Batch Sync API → Update Price & Stock → E-commerce Platform
```

---

## Files

| File                              | Responsibility                                        |
| --------------------------------- | ----------------------------------------------------- |
| `SapIntegrationService.php`       | Builds and sends order payload to SAP middleware      |
| `SapWebhookHandler.php`           | Receives and processes inbound webhooks from SAP      |
| `SapWebhookController.php`        | HTTP entry point for inbound webhook                  |
| `SapProductSyncController.php`    | HTTP entry point for product sync from SAP            |
| `SapProductSyncService.php`       | Processes product price and stock updates             |
| `KraReceiptService.php`           | Generates and emails KRA-compliant receipt PDF        |
| `SapApiException.php`             | Exception for non-2xx SAP API responses               |
| `ValueObjects/SapSyncResult.php`  | Return value from `syncOrder()` — carries `docNumber` and `docEntry` |
| `ValueObjects/CuNumberResult.php` | Structured CU number data from the inbound webhook    |
| `Jobs/SyncOrderToSapJob.php`      | Queued job — dispatched after payment confirmed       |

---

## Status Flow

```
pending → syncing → cu_pending → cu_received
              ↓
           failed  (after 3 retries → admin notified)

cu_pending → returned  (when SAP notifies us the order was returned)
```

| Status        | Meaning                                              |
| ------------- | ---------------------------------------------------- |
| `pending`     | Order paid, job queued                               |
| `syncing`     | API call in progress                                 |
| `cu_pending`  | Invoice created in SAP, awaiting KRA CU number       |
| `cu_received` | CU number stored, receipt sent to customer           |
| `returned`    | SAP notified us the order was returned               |
| `failed`      | All retries exhausted — admin alerted                |

---

## Outbound — System → SAP

**Endpoint**: `POST {SAP_BASE_URL}/api/invoice/create`

**Trigger**: `SyncOrderToSapJob` dispatched after payment is confirmed.

The middleware handles Sales Order + A/R Invoice + Incoming Payment creation internally — we send a single combined payload.

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
            "debit_total_price": 15750.0
        }
    }
}
```

**Expected response**:

```json
{
    "success": true,
    "message": "Invoice created successfully",
    "docEntry": 23293
}
```

`docNumber` may also be present but is not guaranteed:

```json
{
    "success": true,
    "message": "Invoice created successfully",
    "docEntry": 23293,
    "docNumber": "INV-2024-001234"
}
```

| Field       | Required | Description                                       | Stored on order  |
| ----------- | -------- | ------------------------------------------------- | ---------------- |
| `docEntry`  | Always   | SAP internal primary key (used to link documents) | `sap_doc_entry`  |
| `docNumber` | Optional | Human-readable SAP document number                | `sap_doc_number` |

---

## Inbound Webhook — SAP → System

**Endpoint**: `POST /api/webhooks/sap`

**Security**: `X-SAP-Secret` header validated with `hash_equals()` to prevent timing attacks.

SAP sends two types of webhook:

### 1. KRA CU Number (order validated by eTIMS)

**Payload**:

```json
{
    "external_reference": "SO-2026-000001",
    "cu_number": "CU123456789012345678",
    "validated_at": "2024-03-31T14:30:00Z"
}
```

| Field                | Description                                        |
| -------------------- | -------------------------------------------------- |
| `external_reference` | Our order reference — used to look up the order    |
| `cu_number`          | KRA-issued Control Unit number                     |
| `validated_at`       | Timestamp when KRA validated the invoice           |

**What happens**: `kra_cu_number` and `kra_validated_at` are stored on the order, status moves to `cu_received`, and `KraReceiptService` generates the PDF receipt and emails it to the customer.

The handler is **idempotent** — if the same CU number arrives twice for the same order, the second delivery is silently ignored.

### 2. Order Returned

**Payload**:

```json
{
    "external_reference": "SO-2026-000001",
    "status": "returned"
}
```

**What happens**: The order's `sap_sync_status` is updated to `returned` and the event is logged.

**Response codes**:

- `200` — success (always returned on valid requests so SAP doesn't retry unnecessarily)
- `401` — invalid or missing `X-SAP-Secret`
- `500` — processing error (SAP will retry)

---

## Product Sync — SAP → System

### Endpoint

**URL**: `POST /api/sap/products/sync`

**Authentication**: `X-SAP-Secret` header

### Request

```json
{
    "products": [
        {
            "sku": "PROD-12345",
            "price": 1500.0,
            "sale_price": 1200.0,
            "stock_quantity": 50
        },
        {
            "sku": "PROD-67890",
            "price": 2500.0,
            "stock_quantity": 30
        }
    ]
}
```

**Per product fields**:

| Field            | Required | Description                                  |
| ---------------- | -------- | -------------------------------------------- |
| `sku`            | Yes      | Must match an existing product in the DB     |
| `price`          | Yes      | Regular price                                |
| `sale_price`     | No       | Promotional price                            |
| `stock_quantity` | Yes      | Available stock                              |
| `manage_stock`   | No       | Defaults to `true`                           |

### Response

```json
{
    "success": true,
    "message": "Batch sync completed",
    "total": 2,
    "successful": 2,
    "failed": 0,
    "results": [
        { "success": true, "sku": "PROD-12345", "product_id": 123 },
        { "success": true, "sku": "PROD-67890", "product_id": 456 }
    ]
}
```

**Notes**:
- Products are matched by `sku` — the product must already exist.
- Only `price`, `sale_price`, `stock_quantity`, `stock_status`, and `sap_last_synced_at` are updated. Name, description, and images are untouched.
- `stock_status` is derived automatically: `in_stock` when `stock_quantity > 0`, otherwise `out_of_stock`.
- Each product in the batch is processed independently — one failure does not stop the rest.
- Recommended batch size: 100–500 products per request.

---

## Configuration

```env
SAP_BASE_URL=https://sap-middleware.yourdomain.com
SAP_WEBHOOK_SECRET=your-shared-secret
```

Config file: `config/sap.php`

Generate a secure secret and share it with the SAP middleware team:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

> Never commit the secret to version control. Rotate it by generating a new value and updating both `.env` and the SAP middleware config simultaneously.

---

## Audit Logs

Every outbound call and inbound webhook is recorded in `sap_sync_logs`:

| `operation`      | When                                      |
| ---------------- | ----------------------------------------- |
| `create_invoice` | Outbound order sync to SAP                |
| `cu_webhook`     | Inbound KRA CU number webhook             |
| `return_webhook` | Inbound order-returned webhook            |

```sql
SELECT * FROM sap_sync_logs WHERE order_id = 123 ORDER BY created_at DESC;
```

Activity log events: `sap_sync_completed`, `sap_sync_failed`, `sap_kra_validated`, `sap_order_returned`

---

## Testing the Webhook Locally

### KRA CU Number Webhook

```bash
curl -X POST http://sheffield-ecommerce.test/api/webhooks/sap \
  -H "Content-Type: application/json" \
  -H "X-SAP-Secret: your-webhook-secret" \
  -d '{
    "external_reference": "SO-2026-000001",
    "cu_number": "CU123456789012345678",
    "validated_at": "2024-03-31T14:30:00Z"
  }'
```

### Order Returned Webhook

```bash
curl -X POST http://sheffield-ecommerce.test/api/webhooks/sap \
  -H "Content-Type: application/json" \
  -H "X-SAP-Secret: your-webhook-secret" \
  -d '{
    "external_reference": "SO-2026-000001",
    "status": "returned"
  }'
```

### Product Sync

```bash
curl -X POST http://sheffield-ecommerce.test/api/sap/products/sync \
  -H "Content-Type: application/json" \
  -H "X-SAP-Secret: your-webhook-secret" \
  -d '{
    "products": [
      {
        "sku": "PROD-12345",
        "price": 1500.00,
        "stock_quantity": 50
      }
    ]
  }'
```
