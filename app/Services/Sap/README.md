# SAP Business One Integration

## Table of Contents

1. [Overview](#overview)
2. [Files](#files)
3. [Order Sync - Status Flow](#status-flow)
4. [Outbound — System → SAP (Order Sync)](#outbound--system--sap)
5. [Inbound Webhook — SAP → System (CU Number)](#inbound-webhook--sap--system)
6. [Product Sync — SAP → System (Price & Stock)](#product-sync--sap--system)
7. [CU Number Delivery](#cu-number-delivery)
8. [Configuration](#configuration)
9. [Audit Logs](#audit-logs)
10. [Testing](#testing)

---

## Overview

Bidirectional integration with SAP Business One via a middleware API for:

- Order sync to SAP and KRA eTIMS compliance
- Product price and stock updates from SAP

```
Order Paid → SyncOrderToSapJob → SAP Middleware → CU Number → KRA Receipt → Customer
SAP Products → Batch Sync API → Update Price & Stock → E-commerce Platform
```

---

## Files

| File                              | Responsibility                                    |
| --------------------------------- | ------------------------------------------------- |
| `SapIntegrationService.php`       | Builds and sends order payload to SAP middleware  |
| `SapWebhookHandler.php`           | Receives and processes inbound CU number webhooks |
| `SapWebhookController.php`        | HTTP entry point for inbound webhook              |
| `SapProductSyncController.php`    | HTTP entry point for product sync from SAP        |
| `SapProductSyncService.php`       | Processes product price and stock updates         |
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

**Security**: Simple secret header validation using `X-SAP-Secret`

The webhook validates the secret by comparing the header value with the configured secret using `hash_equals()` to prevent timing attacks.

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

## Product Sync — SAP → System

### Overview

This API allows SAP Business One for HANA to push product price and stock updates to the e-commerce platform in batch.

### Endpoint

**URL**: `POST /api/sap/products/sync`

**Purpose**: Batch update product prices and stock quantities from SAP Business One for HANA

**Authentication**: Simple secret header validation using `X-SAP-Secret`

### Request Format

**Headers**:

```
Content-Type: application/json
X-SAP-Secret: your-secret-key-here
```

**Body**:

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
        },
        {
            "sku": "PROD-11111",
            "price": 750.0,
            "sale_price": 600.0,
            "stock_quantity": 100
        }
    ]
}
```

### Field Descriptions

**Per Product:**

- `sku` (required, string): Product SKU - must match existing product in database
- `price` (required, number): Regular price
- `sale_price` (optional, number): Sale/promotional price
- `cost_price` (optional, number): Cost price for internal tracking
- `stock_quantity` (required, integer): Available stock quantity
- `manage_stock` (optional, boolean): Whether to manage stock for this product (default: true)

### Response Format

**Success Response (200)**:

```json
{
    "success": true,
    "message": "Batch sync completed",
    "total": 3,
    "successful": 2,
    "failed": 1,
    "results": [
        {
            "success": true,
            "sku": "PROD-12345",
            "product_id": 123
        },
        {
            "success": true,
            "sku": "PROD-67890",
            "product_id": 456
        },
        {
            "success": false,
            "sku": "PROD-11111",
            "error": "Product with SKU PROD-11111 not found"
        }
    ]
}
```

**Error Responses**:

401 Unauthorized - Invalid secret:

```json
{
    "success": false,
    "message": "Invalid webhook secret"
}
```

422 Validation Error - No products provided:

```json
{
    "success": false,
    "message": "No products provided"
}
```

500 Server Error:

```json
{
    "success": false,
    "message": "Batch sync failed: [error details]"
}
```

### Integration Notes

**Product Matching**:

- Products are matched by `sku` field
- The product must already exist in the database
- If a product is not found, it will be marked as failed in the results but won't stop the batch

**What Gets Updated**:

The API updates only:

1. **Price fields**: `price`, `sale_price`, `cost_price`
2. **Stock fields**: `stock_quantity`, `stock_status`
3. **Sync tracking**: `sap_last_synced_at` timestamp

All other product fields (name, description, images, etc.) are NOT modified.

**Stock Status**:

The `stock_status` field is automatically calculated:

- `in_stock` when `stock_quantity > 0`
- `out_of_stock` when `stock_quantity = 0`

**Sync Tracking**:

Every successful sync updates the `sap_last_synced_at` timestamp on the product record for audit purposes.

**Batch Processing**:

- Each product in the batch is processed independently
- Failed products don't affect successful ones
- The response includes detailed results for each product
- Recommended batch size: 100-500 products per request

### Error Handling

**Retry Logic**:

- **401 errors**: Do not retry - fix authentication
- **422 errors**: Do not retry - fix validation errors in payload
- **500 errors**: Retry with exponential backoff

**Partial Success**:

The API processes all products even if some fail. Check the `results` array in the response to identify which products succeeded and which failed.

**Logging**:

All sync attempts are logged with:

- Request payload
- Response status
- Error messages (if any)
- Timestamp
- Individual product results

Check application logs for detailed error information.

### Testing

**Test Without Authentication (Development Only)**:

If `SAP_WEBHOOK_SECRET` is not set in `.env`, authentication is skipped. This is useful for testing but NOT recommended for production.

**Recommended Testing Flow**:

1. Create test products in the admin panel with known SKUs
2. Use the batch sync endpoint to update their prices and stock
3. Verify the changes in the admin panel
4. Test error scenarios (invalid SKU, missing fields, etc.)
5. Test with large batches (100+ products)

**Example cURL Request**:

```bash
curl -X POST https://your-domain.com/api/sap/products/sync \
  -H "Content-Type: application/json" \
  -H "X-SAP-Secret: your-secret-key-here" \
  -d '{
    "products": [
      {
        "sku": "PROD-12345",
        "price": 1500.00,
        "stock_quantity": 50
      },
      {
        "sku": "PROD-67890",
        "price": 2500.00,
        "sale_price": 2200.00,
        "stock_quantity": 30
      }
    ]
  }'
```

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

Generate a secure secret and share it with the SAP middleware team. They will include it in the `X-SAP-Secret` header for all webhook requests.

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

All options produce a 64-character hex string, e.g.:

```
a3f8c2d1e4b5a6f7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1
```

Add it to `.env`:

```env
SAP_WEBHOOK_SECRET=a3f8c2d1e4b5a6f7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1
```

> **Important**: Never commit the secret to version control. Rotate it by generating a new value and updating both `.env` and the SAP middleware configuration simultaneously.

---

## Audit Logs

Every outbound call and inbound webhook is recorded in `sap_sync_logs`:

```sql
SELECT * FROM sap_sync_logs WHERE order_id = 123 ORDER BY created_at DESC;
```

Activity log events: `sap_sync_success`, `sap_sync_failed`

---

## Testing the Webhook Locally

### Order Webhook (CU Number)

```bash
curl -X POST http://localhost/api/webhooks/sap \
  -H "Content-Type: application/json" \
  -H "X-SAP-Secret: your-webhook-secret" \
  -d '{
    "event": "invoice.cu_number_generated",
    "data": {
      "external_reference": "ORD-001",
      "cu_number": "CU123456789012345678",
      "kra_invoice_number": "INV-KRA-001",
      "validated_at": "2024-03-31T14:30:00Z"
    }
  }'
```

### Product Sync

```bash
curl -X POST http://localhost/api/sap/products/sync \
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
