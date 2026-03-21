<?php

namespace App\Services;

use App\Enums\OrdersStatus;
use App\Models\Order;
use App\Notifications\QuoteAcceptedNotification;
use App\Notifications\QuoteRejectedNotification;
use App\Notifications\QuoteRequestedNotification;
use App\Notifications\QuoteSentNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class QuotationService
{
    // =========================================================================
    // ADMIN NOTIFICATION ROUTING
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
    // CREATE FROM BASKET (customer → new product quotation)
    //
    // Called from the /quote page when the customer submits the form.
    //
    // $data array shape:
    // [
    //   'preferred_county'        => string|null,
    //   'preferred_area'          => string|null,
    //   'customer_notes'          => string|null,
    //   // Guest-only fields (ignored when Auth::check()):
    //   'name'                    => string,
    //   'email'                   => string,
    //   'phone'                   => string,
    // ]
    //
    // What it does:
    //   1. Creates Order (document_type=quotation, quotation_type=product)
    //   2. Creates OrderItems from basket with product snapshots
    //   3. Clears the session basket
    //   4. Calls notifyRequested() to alert admin
    //
    // Returns the new Order so caller can redirect to confirmation page.
    // =========================================================================

    public function createFromBasket(QuoteBasketService $basket, array $data): Order
    {
        if ($basket->isEmpty()) {
            throw new \RuntimeException('Quote basket is empty.');
        }

        $items = $basket->hydratedItems();

        $subtotalCents = (int) $items->sum(
            fn($item) => round($item['unit_price'] * $item['quantity'] * 100)
        );

        $order = DB::transaction(function () use ($items, $subtotalCents, $data) {

            $order = Order::create([
                'user_id'                 => Auth::id(),
                'reference'               => Order::generateReference('quotation'),
                'document_type'           => 'quotation',
                'quotation_type'          => 'product',
                'status'                  => OrdersStatus::PENDING_QUOTE->value,
                'payment_status'          => 'pending',
                'currency'                => 'KES',
                'subtotal_cents'          => $subtotalCents,
                'total_cents'             => $subtotalCents,
                'preferred_county'        => $data['preferred_county'] ?? null,
                'preferred_area'          => $data['preferred_area'] ?? null,
                'customer_notes'          => $data['customer_notes'] ?? null,
                'guest_info'              => Auth::check() ? null : [
                    'name'  => $data['name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ],
            ]);

            $order->statusHistories()->create([
                'from_status'     => null,
                'to_status'       => OrdersStatus::PENDING_QUOTE->value,
                'changed_by_type' => 'user',
                'notes'           => 'Quotation request submitted by customer.',
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                $variant = $item['variant'];

                $unitPriceCents = (int) round($item['unit_price'] * 100);

                $order->items()->create([
                    'product_id'         => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity'           => $item['quantity'],
                    'unit_price_cents'   => $unitPriceCents,
                    'unit_tax_cents'     => 0,
                    'discount_cents'     => 0,
                    'total_cents'        => $unitPriceCents * $item['quantity'],
                    'product_snapshot'   => [
                        'name'      => $product->name,
                        'sku'       => $variant?->sku ?? $product->sku,
                        'image_url' => $product->image_url,
                        'brand'     => $product->brand?->name,
                        'variant'   => $variant
                            ? $variant->attributeValues
                            ->mapWithKeys(fn($av) => [$av->attribute->name => $av->label ?: $av->value])
                            ->toArray()
                            : null,
                    ],
                ]);
            }

            return $order;
        });

        $basket->clear();

        $this->notifyRequested($order);

        return $order;
    }

    // =========================================================================
    // QUOTE REQUESTED (admin notification)
    //
    // Called after createFromBasket() and also after the delivery quotation
    // checkout flow creates the order.
    // =========================================================================

    public function notifyRequested(Order $order): void
    {
        try {
            $this->notifyAdmin(new QuoteRequestedNotification($order));
        } catch (\Throwable $e) {
            Log::error('Failed to send QuoteRequestedNotification.', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // SEND QUOTE (admin → customer)
    //
    // Called from the admin quotation show page when admin submits pricing.
    //
    // $pricing array shape:
    // [
    //   'shipping'      => float,
    //   'validity_days' => int,
    //   'note'          => string|null,
    //   'item_prices'   => array|null,  // [item_id => price] for product quotes
    // ]
    // =========================================================================

    public function send(Order $order, array $pricing): Order
    {
        $shippingCents = (int) round((float) ($pricing['shipping'] ?? 0) * 100);
        $validityDays  = max(1, (int) ($pricing['validity_days'] ?? 7));
        $note          = $pricing['note'] ?? null;
        $itemPrices    = $pricing['item_prices'] ?? [];

        DB::transaction(function () use ($order, $shippingCents, $validityDays, $note, $itemPrices) {

            if ($order->isProductQuotation() && !empty($itemPrices)) {
                $subtotalCents = 0;

                foreach ($order->items as $item) {
                    $newUnitPriceCents = isset($itemPrices[$item->id])
                        ? (int) round((float) $itemPrices[$item->id] * 100)
                        : $item->unit_price_cents;

                    $newItemTotal = $newUnitPriceCents * $item->quantity;

                    $item->update([
                        'unit_price_cents' => $newUnitPriceCents,
                        'total_cents'      => $newItemTotal,
                    ]);

                    $subtotalCents += $newItemTotal;
                }

                $totalCents = max(0, $subtotalCents - $order->discount_cents + $shippingCents);

                $order->update([
                    'subtotal_cents' => $subtotalCents,
                    'shipping_cents' => $shippingCents,
                    'total_cents'    => $totalCents,
                ]);
            } else {
                $totalCents = max(
                    0,
                    $order->subtotal_cents - $order->discount_cents + $shippingCents
                );

                $order->update([
                    'shipping_cents' => $shippingCents,
                    'total_cents'    => $totalCents,
                ]);
            }

            $order->update([
                'expires_at' => now()->addDays($validityDays),
                'quoted_at'  => now(),
            ]);

            $order->transitionTo(
                OrdersStatus::QUOTE_SENT,
                notes: $note ?: "Quotation priced and sent to customer. Valid for {$validityDays} day(s).",
                changedByType: 'user'
            );
        });

        $order->refresh();

        app(DocumentService::class)->generateQuotation($order);

        try {
            // Use customerEmail() so guest quotations also get notified
            if ($order->user) {
                $order->user->notify(new QuoteSentNotification($order));
            } else {
                Notification::route('mail', $order->customerEmail())
                    ->notify(new QuoteSentNotification($order));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send QuoteSentNotification.', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }

        return $order;
    }

    // =========================================================================
    // ACCEPT QUOTE (customer → sales order)
    // =========================================================================

    public function accept(Order $order): Order
    {
        $salesOrder = DB::transaction(function () use ($order) {
            $order->transitionTo(
                OrdersStatus::QUOTE_ACCEPTED,
                notes: 'Customer accepted the quotation.',
                changedByType: 'user'
            );

            return $order->convertToSalesOrder();
        });

        try {
            $this->notifyAdmin(new QuoteAcceptedNotification($order, $salesOrder));
        } catch (\Throwable $e) {
            Log::error('Failed to send QuoteAcceptedNotification.', [
                'quotation_id'   => $order->id,
                'sales_order_id' => $salesOrder->id,
                'error'          => $e->getMessage(),
            ]);
        }

        return $salesOrder;
    }

    // =========================================================================
    // REJECT QUOTE (customer → terminal)
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

        try {
            $this->notifyAdmin(new QuoteRejectedNotification($order));
        } catch (\Throwable $e) {
            Log::error('Failed to send QuoteRejectedNotification.', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // CANCEL QUOTE (admin → terminal)
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
    // EXPIRE QUOTES (system → terminal, called by scheduled command)
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
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    // =========================================================================
    // ATTACH GUEST QUOTES (on registration/login)
    //
    // Finds all guest quotations with matching email and attaches them
    // to the now-authenticated user account.
    // =========================================================================

    public function attachGuestQuotes(string $email, int $userId): int
    {
        $orphaned = Order::where('document_type', 'quotation')
            ->whereNull('user_id')
            ->whereJsonContains('guest_info->email', $email)
            ->get();

        $count = 0;

        foreach ($orphaned as $order) {
            $order->update([
                'user_id'    => $userId,
                'guest_info' => null,
            ]);
            $count++;
        }

        return $count;
    }
}
