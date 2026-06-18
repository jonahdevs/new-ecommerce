# WhatsApp Message Templates

Reference for the Meta WhatsApp Cloud API templates this app sends. Create/submit
each in **WhatsApp Manager → Message Templates**.

## Rules / gotchas
- **Language:** create as **English (US)** → code `en_US` (the `WhatsAppMessage` default).
  If you create plain "English" (`en`), call `->language('en')` in the notification.
- A `{{variable}}` **cannot be the first or last** thing in the body — wrap with static text.
- The **number of `{{n}}` placeholders must match** the `->body(...)` arg count exactly,
  or Meta rejects the send with error **#132001**.
- Templates must be **Approved** before they can be sent (pending/in-review = "does not exist").
- Category: **Utility** for all of these (transactional).

## How sending works
- `App\Notifications\Messages\WhatsAppMessage::template('name')->body($p1, $p2, ...)`.
- `App\Notifications\Channels\WhatsAppChannel` posts to the Graph API.
- Credentials: `NotificationSettings` (admin) first, else `.env`
  (`WHATSAPP_API_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`).
- Routing: `User::routeNotificationForWhatsapp()` returns `users.phone` (E.164).
- Gating: customer notifs via `RespectsPreferences`; staff via `RespectsStaffPreferences`.
  Both require the matching global toggle ON + recipient has a phone.

## Templates

### order_confirmed — `OrderConfirmed`
Vars: `{{1}}` name · `{{2}}` order# · `{{3}}` total · `{{4}}` payment method · `{{5}}` order URL
```
Hi {{1}}, your order {{2}} has been confirmed ✅

Total: {{3}}
Payment method: {{4}}

View your order details here: {{5}}

Thank you for shopping with us!
```
Samples: `Jonah` · `ORD-2026-00184` · `KES 12,500.00` · `M-Pesa` · `https://.../account/orders/184`

### order_status_update — `OrderStatusChanged`
Vars: `{{1}}` name · `{{2}}` order# · `{{3}}` status label (Out for delivery / Completed / Cancelled)
```
Hi {{1}}, the status of your order {{2}} has changed to: {{3}}. Check your account for full details.
```

### refund_processed — `RefundProcessed`
Vars: `{{1}}` name · `{{2}}` order# · `{{3}}` refund amount
```
Hi {{1}}, a refund of {{3}} has been processed for your order {{2}}. It may take a few days to reflect. Thank you.
```

### quote_ready — `QuoteReadyForReview`
Vars: `{{1}}` name (user or contact_name) · `{{2}}` quote#
```
Hi {{1}}, your quotation {{2}} is ready for review. Log in to your account to view and approve it. Thank you.
```

### quote_received — `QuoteRequestReceived`
Vars: `{{1}}` name (user or contact_name) · `{{2}}` quote#
```
Hi {{1}}, we've received your quote request {{2}} and our team is preparing it. We'll be in touch shortly. Thank you.
```

### staff_new_order — `NewOrderReceived`
Vars: `{{1}}` customer · `{{2}}` order# · `{{3}}` total
```
New order received: {{2}} from {{1}}, total {{3}}. Open the admin panel to process it.
```

### staff_low_stock — `LowStockAlert`
Vars: `{{1}}` product name · `{{2}}` quantity
```
Low stock alert: "{{1}}" has {{2}} unit(s) remaining. Please restock soon.
```

### staff_new_quote — `NewQuoteRequested`
Vars: `{{1}}` customer · `{{2}}` quote# · `{{3}}` item count
```
New quote request {{2}} from {{1}} with {{3}} item(s) to price. Open the admin panel to prepare it.
```

### staff_quote_decision — `QuoteDecisionReceived`
Vars: `{{1}}` customer · `{{2}}` quote# · `{{3}}` decision (approved / declined)
```
Customer {{1}} has {{3}} quotation {{2}}. Open the admin panel for next steps.
```

## Global toggles (NotificationSettings)
- `customer_order_confirmation_whatsapp` → order_confirmed
- `customer_order_updates_whatsapp` → order_status_update, refund_processed
- `customer_quote_updates_whatsapp` → quote_ready
- `customer_quote_received_whatsapp` → quote_received
- `staff_new_order_whatsapp` · `staff_low_stock_whatsapp` · `staff_new_quote_whatsapp` · `staff_quote_decision_whatsapp`
