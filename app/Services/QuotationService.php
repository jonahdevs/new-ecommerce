<?php

namespace App\Services;

use App\Enums\OrdersStatus;
use App\Models\Order;
use App\Notifications\QuoteAcceptedNotification;
use App\Notifications\QuoteRejectedNotification;
use App\Notifications\QuoteRequestedNotification;
use App\Notifications\QuoteSentNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class QuotationService
{
    // =========================================================================
    //  Admin notification address
    //
    //  All admin-bound notifications go to a single email address configured
    //  in services.quotation.admin_email (set via .env QUOTATION_ADMIN_EMAIL).
    //  This avoids coupling the notification system to user roles and keeps
    //  the routing easy to change without touching code.
    // =========================================================================

    private function adminEmail(): string
    {
        return config('services.quotation.admin_email', config('mail.from.address'));
    }

    private function notifyAdmin(mixed $notification): void
    {
        Notification::route('mail', $this->adminEmail())
            ->notify($notification);
    }

    // =========================================================================
    //  QUOTE REQUESTED
    //
    //  Called immediately after the quotation Order record is created
    //  in the order-summary component (processQuoteRequest).
    //
    //  Notifies the admin team that a new quotation is awaiting pricing.
    //  The customer does NOT receive a notification here — they already
    //  land on the quote-success confirmation page.
    // =========================================================================

    public function notifyRequested(Order $order): void
    {
        try {
            $this->notifyAdmin(new QuoteRequestedNotification($order));
        } catch (\Throwable $e) {
            // Notification failure must never break the checkout flow.
            // Log it and move on — the quote is already saved.
            Log::error('Failed to send QuoteRequestedNotification.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    //  SEND QUOTE (admin → customer)
    //
    //  Called from the admin quotation show page when admin submits the
    //  pricing form.
    //
    //  $pricing array shape:
    //  [
    //    'shipping'      => float,          // quoted shipping in KES
    //    'validity_days' => int,            // how many days before quote expires
    //    'note'          => string|null,    // optional admin note
    //    'item_prices'   => array|null,     // [item_id => price] for product quotes
    //  ]
    //
    //  What it does:
    //    1. Updates financial fields on the quotation
    //    2. Sets expires_at and quoted_at
    //    3. Transitions status → QUOTE_SENT
    //    4. Sends QuoteSentNotification to the customer
    //
    //  Returns the updated Order.
    // =========================================================================

    public function send(Order $order, array $pricing): Order
    {
        $shippingCents = (int) round((float) ($pricing['shipping'] ?? 0) * 100);
        $validityDays = max(1, (int) ($pricing['validity_days'] ?? 7));
        $note = $pricing['note'] ?? null;
        $itemPrices = $pricing['item_prices'] ?? [];

        DB::transaction(function () use ($order, $shippingCents, $validityDays, $note, $itemPrices) {

            // ── Product quotation: update each item's unit price ──────────────
            //
            // Admin may adjust prices from the catalogue default.
            // Recalculate item total_cents from new unit price × quantity.

            if ($order->isProductQuotation() && !empty($itemPrices)) {
                $subtotalCents = 0;

                foreach ($order->items as $item) {
                    $newUnitPriceCents = isset($itemPrices[$item->id])
                        ? (int) round((float) $itemPrices[$item->id] * 100)
                        : $item->unit_price_cents;

                    $newItemTotal = $newUnitPriceCents * $item->quantity;

                    $item->update([
                        'unit_price_cents' => $newUnitPriceCents,
                        'total_cents' => $newItemTotal,
                    ]);

                    $subtotalCents += $newItemTotal;
                }

                $totalCents = max(0, $subtotalCents - $order->discount_cents + $shippingCents);

                $order->update([
                    'subtotal_cents' => $subtotalCents,
                    'shipping_cents' => $shippingCents,
                    'total_cents' => $totalCents,
                ]);

            } else {
                // ── Delivery quotation: only shipping cost is new ─────────────
                //
                // Product prices are already set from the catalogue.
                // Add the quoted shipping on top of existing subtotal.

                $totalCents = max(
                    0,
                    $order->subtotal_cents - $order->discount_cents + $shippingCents
                );

                $order->update([
                    'shipping_cents' => $shippingCents,
                    'total_cents' => $totalCents,
                ]);
            }

            // Set validity window and record when admin sent the quote
            $order->update([
                'expires_at' => now()->addDays($validityDays),
                'quoted_at' => now(),
            ]);

            // Transition and record in status history
            $order->transitionTo(
                OrdersStatus::QUOTE_SENT,
                notes: $note ?: "Quotation priced and sent to customer. Valid for {$validityDays} day(s).",
                changedByType: 'user'
            );
        });

        $order->refresh();

        // Generate and store the quotation PDF
        app(DocumentService::class)->generateQuotation($order);

        // Notify customer — outside transaction, failure must not roll back
        try {
            $order->user->notify(new QuoteSentNotification($order));
        } catch (\Throwable $e) {
            Log::error('Failed to send QuoteSentNotification.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $order;
    }

    // =========================================================================
    //  ACCEPT QUOTE (customer → sales order)
    //
    //  Called from the customer portal when the customer clicks Accept.
    //
    //  What it does:
    //    1. Transitions quotation → QUOTE_ACCEPTED
    //    2. Calls Order::convertToSalesOrder() to create the linked SO
    //    3. Notifies admin
    //
    //  Returns the new sales Order so the caller can redirect to payment.
    // =========================================================================

    public function accept(Order $order): Order
    {
        $salesOrder = DB::transaction(function () use ($order) {

            // Transition the quotation to accepted first
            $order->transitionTo(
                OrdersStatus::QUOTE_ACCEPTED,
                notes: 'Customer accepted the quotation.',
                changedByType: 'user'
            );

            // Create the linked sales order from the accepted quotation
            return $order->convertToSalesOrder();
        });

        // Notify admin — outside transaction
        try {
            $this->notifyAdmin(new QuoteAcceptedNotification($order, $salesOrder));
        } catch (\Throwable $e) {
            Log::error('Failed to send QuoteAcceptedNotification.', [
                'quotation_id' => $order->id,
                'sales_order_id' => $salesOrder->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $salesOrder;
    }

    // =========================================================================
    //  REJECT QUOTE (customer → terminal)
    //
    //  Called from the customer portal when the customer clicks Reject.
    //
    //  Transitions quotation → QUOTE_REJECTED (terminal).
    //  Admin is notified so they can follow up if appropriate.
    // =========================================================================

    public function reject(Order $order, ?string $note = null): void
    {
        DB::transaction(function () use ($order, $note) {
            $order->transitionTo(
                OrdersStatus::QUOTE_REJECTED,
                notes: $note ?: 'Customer rejected the quotation.',
                changedByType: 'user'
            );
        });

        // Notify admin — outside transaction
        try {
            $this->notifyAdmin(new QuoteRejectedNotification($order));
        } catch (\Throwable $e) {
            Log::error('Failed to send QuoteRejectedNotification.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    //  CANCEL QUOTE (admin → terminal)
    //
    //  Called from the admin show page.
    //  No customer notification — admin handles communication directly.
    // =========================================================================

    public function cancel(Order $order, ?string $note = null): void
    {
        DB::transaction(function () use ($order, $note) {
            $order->transitionTo(
                OrdersStatus::CANCELLED,
                notes: $note ?: 'Cancelled by admin.',
                changedByType: 'user'
            );
        });
    }

    // =========================================================================
    //  EXPIRE QUOTES (system → terminal)
    //
    //  Called by the ExpireQuotations scheduled command.
    //  Transitions all QUOTE_SENT orders whose expires_at < now()
    //  to QUOTE_EXPIRED in a single batch.
    //
    //  changedByType is 'system' so the status history clearly shows
    //  these were automatic transitions, not manual admin actions.
    // =========================================================================

    public function expireOverdue(): int
    {
        $expired = Order::where('document_type', 'quotation')
            ->where('status', OrdersStatus::QUOTE_SENT->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expired as $order) {
            try {
                DB::transaction(function () use ($order) {
                    $order->transitionTo(
                        OrdersStatus::QUOTE_EXPIRED,
                        notes: 'Quote expired — no customer response before validity period ended.',
                        changedByType: 'system'
                    );
                });
                $count++;
            } catch (\Throwable $e) {
                Log::error('Failed to expire quotation.', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
