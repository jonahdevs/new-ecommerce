<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SapSyncStatus;
use App\Models\Quote;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\QuoteAcceptedNotification;
use App\Notifications\QuoteExpiringNotification;
use App\Notifications\QuoteRejectedNotification;
use App\Notifications\QuoteRequestedNotification;
use App\Notifications\QuoteSentNotification;
use App\Services\Payment\PaymentService;
use App\Settings\CustomerNotificationSettings;
use App\Settings\LocalizationSettings;
use App\Settings\NotificationSettings;
use App\Settings\QuotationSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class QuotationService
{
    public function __construct(
        private readonly LocalizationSettings $localization,
        private readonly QuotationSettings $quotationSettings,
        private readonly NotificationSettings $notificationSettings,
        private readonly CustomerNotificationSettings $customerNotificationSettings,
        private readonly PaymentService $paymentService,
    ) {}

    // =========================================================================
    // ADMIN NOTIFICATION ROUTING
    // =========================================================================

    private function adminEmail(): string
    {
        // Priority: quotation-specific email > general notification email > mail from address
        return $this->quotationSettings->admin_notification_email
            ?? $this->notificationSettings->admin_notification_email
            ?? config('mail.from.address');
    }

    private function notifyAdmin(mixed $notification): void
    {
        Notification::route('mail', $this->adminEmail())
            ->notify($notification);
    }

    // =========================================================================
    // CHECK IF QUOTATIONS ARE ENABLED
    // =========================================================================

    public function isEnabled(): bool
    {
        return $this->quotationSettings->enabled;
    }

    public function allowsGuestQuotes(): bool
    {
        return $this->quotationSettings->allow_guest_quotes;
    }

    public function requiresPhone(): bool
    {
        return $this->quotationSettings->require_phone;
    }

    public function getDefaultValidityDays(): int
    {
        return $this->quotationSettings->default_validity_days;
    }

    public function getValidityRange(): array
    {
        return [
            'min' => $this->quotationSettings->min_validity_days,
            'max' => $this->quotationSettings->max_validity_days,
        ];
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
    //   1. Creates Quote with status PENDING
    //   2. Creates QuoteItems from basket with product snapshots
    //   3. Clears the session basket
    //   4. Calls notifyRequested() to alert admin
    //
    // Returns the new Quote so caller can redirect to confirmation page.
    // =========================================================================

    public function createFromBasket(QuoteBasketService $basket, array $data): Quote
    {
        if ($basket->isEmpty()) {
            throw new \RuntimeException('Quote basket is empty.');
        }

        $items = $basket->hydratedItems();

        $subtotalCents = (int) $items->sum(
            fn($item) => round($item['unit_price'] * $item['quantity'] * 100)
        );

        $quote = DB::transaction(function () use ($items, $subtotalCents, $data) {

            $quote = Quote::create([
                'user_id'          => Auth::id(),
                'reference'        => Quote::generateReference(),
                'status'           => QuoteStatus::PENDING,
                'currency'         => $this->localization->currency,
                'subtotal_cents'   => $subtotalCents,
                'discount_cents'   => 0,
                'shipping_cents'   => 0,
                'tax_cents'        => 0,
                'total_cents'      => $subtotalCents,
                'preferred_county' => $data['preferred_county'] ?? null,
                'preferred_area'   => $data['preferred_area'] ?? null,
                'customer_notes'   => $data['customer_notes'] ?? null,
                'guest_info'       => Auth::check() ? null : [
                    'name'  => $data['name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ],
            ]);

            $quote->statusHistories()->create([
                'from_status'     => null,
                'to_status'       => QuoteStatus::PENDING->value,
                'changed_by_type' => 'user',
                'notes'           => 'Quotation request submitted by customer.',
            ]);

            foreach ($items as $item) {
                $product = $item['product'];
                $variant = $item['variant'];

                $unitPriceCents = (int) round($item['unit_price'] * 100);

                $quote->items()->create([
                    'product_id'           => $product->id,
                    'product_variant_id'   => $variant?->id,
                    'quantity'             => $item['quantity'],
                    'original_price_cents' => $unitPriceCents,
                    'quoted_price_cents'   => null,
                    'product_snapshot'     => [
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

            return $quote;
        });

        $basket->clear();

        $this->notifyRequested($quote);

        return $quote;
    }

    // =========================================================================
    // QUOTE REQUESTED (admin notification)
    // =========================================================================

    public function notifyRequested(Quote $quote): void
    {
        if (!$this->notificationSettings->notify_new_quote) {
            return;
        }

        try {
            $this->notifyAdmin(new QuoteRequestedNotification($quote));
        } catch (\Throwable $e) {
            Log::error('Failed to send QuoteRequestedNotification.', [
                'quote_id' => $quote->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // PREPARE QUOTE (admin saves pricing without sending)
    //
    // Called from the admin quotation show page when admin wants to save
    // pricing without sending to customer yet.
    //
    // $pricing array shape:
    // [
    //   'shipping'      => float,
    //   'validity_days' => int,
    //   'note'          => string|null,
    //   'item_prices'   => array|null,  // [item_id => price] for product quotes
    // ]
    // =========================================================================

    public function prepare(Quote $quote, array $pricing): Quote
    {
        $shippingCents = (int) round((float) ($pricing['shipping'] ?? 0) * 100);
        $validityDays  = max(1, (int) ($pricing['validity_days'] ?? 7));
        $note          = $pricing['note'] ?? null;
        $itemPrices    = $pricing['item_prices'] ?? [];

        DB::transaction(function () use ($quote, $shippingCents, $validityDays, $note, $itemPrices) {

            if (!empty($itemPrices)) {
                $subtotalCents = 0;

                foreach ($quote->items as $item) {
                    $newUnitPriceCents = isset($itemPrices[$item->id])
                        ? (int) round((float) $itemPrices[$item->id] * 100)
                        : ($item->quoted_price_cents ?? $item->original_price_cents);

                    $item->update([
                        'quoted_price_cents' => $newUnitPriceCents,
                    ]);

                    $subtotalCents += $newUnitPriceCents * $item->quantity;
                }

                $totalCents = max(0, $subtotalCents - $quote->discount_cents + $shippingCents);

                $quote->update([
                    'subtotal_cents' => $subtotalCents,
                    'shipping_cents' => $shippingCents,
                    'total_cents'    => $totalCents,
                ]);
            } else {
                $totalCents = max(
                    0,
                    $quote->subtotal_cents - $quote->discount_cents + $shippingCents
                );

                $quote->update([
                    'shipping_cents' => $shippingCents,
                    'total_cents'    => $totalCents,
                ]);
            }

            // Store admin notes if provided
            if ($note) {
                $quote->update(['admin_notes' => $note]);
            }
        });

        return $quote->refresh();
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

    public function send(Quote $quote, array $pricing): Quote
    {
        $shippingCents = (int) round((float) ($pricing['shipping'] ?? 0) * 100);
        $validityDays  = max(1, (int) ($pricing['validity_days'] ?? 7));
        $note          = $pricing['note'] ?? null;
        $itemPrices    = $pricing['item_prices'] ?? [];

        DB::transaction(function () use ($quote, $shippingCents, $validityDays, $note, $itemPrices) {

            if (!empty($itemPrices)) {
                $subtotalCents = 0;

                foreach ($quote->items as $item) {
                    $newUnitPriceCents = isset($itemPrices[$item->id])
                        ? (int) round((float) $itemPrices[$item->id] * 100)
                        : ($item->quoted_price_cents ?? $item->original_price_cents);

                    $item->update([
                        'quoted_price_cents' => $newUnitPriceCents,
                    ]);

                    $subtotalCents += $newUnitPriceCents * $item->quantity;
                }

                $totalCents = max(0, $subtotalCents - $quote->discount_cents + $shippingCents);

                $quote->update([
                    'subtotal_cents' => $subtotalCents,
                    'shipping_cents' => $shippingCents,
                    'total_cents'    => $totalCents,
                ]);
            } else {
                $totalCents = max(
                    0,
                    $quote->subtotal_cents - $quote->discount_cents + $shippingCents
                );

                $quote->update([
                    'shipping_cents' => $shippingCents,
                    'total_cents'    => $totalCents,
                ]);
            }

            $quote->update([
                'expires_at' => now()->addDays($validityDays),
                'quoted_at'  => now(),
            ]);

            $quote->transitionTo(
                QuoteStatus::SENT,
                notes: $note ?: "Quotation priced and sent to customer. Valid for {$validityDays} day(s).",
                changedByType: 'user'
            );
        });

        $quote->refresh();

        app(DocumentService::class)->generateQuotation($quote);

        // Send notification to customer if enabled
        if ($this->customerNotificationSettings->quote_sent) {
            try {
                if ($quote->user) {
                    $quote->user->notify(new QuoteSentNotification($quote));
                } else {
                    Notification::route('mail', $quote->customerEmail())
                        ->notify(new QuoteSentNotification($quote));
                }
            } catch (\Throwable $e) {
                Log::error('Failed to send QuoteSentNotification.', [
                    'quote_id' => $quote->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $quote;
    }

    // =========================================================================
    // ACCEPT QUOTE (customer → sales order)
    // =========================================================================

    public function accept(Quote $quote): Order
    {
        $order = DB::transaction(function () use ($quote) {
            $quote->transitionTo(
                QuoteStatus::ACCEPTED,
                notes: 'Customer accepted the quotation.',
                changedByType: 'user'
            );

            $quote->update(['accepted_at' => now()]);

            return $this->convertToOrder($quote);
        });

        // Send notification to admin if enabled
        if ($this->notificationSettings->notify_quote_accepted) {
            try {
                $this->notifyAdmin(new QuoteAcceptedNotification($quote, $order));
            } catch (\Throwable $e) {
                Log::error('Failed to send QuoteAcceptedNotification.', [
                    'quote_id' => $quote->id,
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $order;
    }

    // =========================================================================
    // CONVERT QUOTE TO ORDER
    // =========================================================================

    private function convertToOrder(Quote $quote): Order
    {
        $user = $quote->user;

        // Resolve shipping address from user's default address
        $address = $user?->addresses()
            ->with(['county', 'area', 'shippingZone'])
            ->where('is_default', true)
            ->first()
            ?? $user?->addresses()->with(['county', 'area', 'shippingZone'])->oldest()->first();

        $addressSnapshot = $address ? [
            'first_name'   => $address->first_name,
            'last_name'    => $address->last_name,
            'full_name'    => $address->full_name,
            'phone_number' => $address->phone_number,
            'address'      => $address->address,
            'area'         => $address->area?->name,
            'county'       => $address->county?->name,
            'zone'         => $address->shippingZone?->name,
        ] : [
            'full_name'    => $quote->customerName(),
            'phone_number' => $quote->customerPhone(),
            'address'      => $quote->preferred_area . ', ' . $quote->preferred_county,
            'area'         => $quote->preferred_area,
            'county'       => $quote->preferred_county,
        ];

        // Shipping snapshot — quote has shipping cost but no method details
        $shippingSnapshot = [
            'method_id'       => null,
            'method_name'     => 'As per quotation',
            'method_code'     => 'quote',
            'method_type'     => 'quote',
            'zone_id'         => null,
            'rate_id'         => null,
            'station_id'      => null,
            'station_name'    => null,
            'cost'            => $quote->shipping,
            'cost_breakdown'  => null,
            'delivery_window' => null,
            'weight_kg'       => null,
        ];

        $order = Order::create([
            'user_id'           => $quote->user_id,
            'quote_id'          => $quote->id,
            'reference'         => Order::generateReference(),
            'status'            => OrderStatus::PENDING,
            'payment_status'    => PaymentStatus::PENDING,
            'currency'          => $quote->currency,
            'subtotal_cents'    => $quote->subtotal_cents,
            'discount_cents'    => $quote->discount_cents,
            'shipping_cents'    => $quote->shipping_cents,
            'tax_cents'         => $quote->tax_cents,
            'total_cents'       => $quote->total_cents,
            'shipping_address'  => $addressSnapshot,
            'billing_address'   => $addressSnapshot,
            'shipping_snapshot' => $shippingSnapshot,
            'sap_sync_status'   => SapSyncStatus::PENDING,
            'sap_sync_attempts' => 0,
            'expires_at'        => now()->addMinutes(30),
        ]);

        foreach ($quote->items as $quoteItem) {
            $order->items()->create([
                'product_id'         => $quoteItem->product_id,
                'product_variant_id' => $quoteItem->product_variant_id,
                'quantity'           => $quoteItem->quantity,
                'unit_price_cents'   => $quoteItem->quoted_price_cents ?? $quoteItem->original_price_cents,
                'unit_tax_cents'     => 0,
                'discount_cents'     => 0,
                'total_cents'        => ($quoteItem->quoted_price_cents ?? $quoteItem->original_price_cents) * $quoteItem->quantity,
                'product_snapshot'   => $quoteItem->product_snapshot,
            ]);
        }

        $order->statusHistories()->create([
            'from_status'     => null,
            'to_status'       => OrderStatus::PENDING->value,
            'changed_by_type' => 'system',
            'notes'           => "Order created from accepted quotation {$quote->reference}.",
        ]);

        // Create the payment record so checkout.pay can initiate the gateway
        Payment::create([
            'order_id'     => $order->id,
            'amount_cents' => $quote->total_cents,
            'currency'     => $quote->currency,
            'status'       => PaymentStatus::PENDING,
            'gateway'      => $this->paymentService->activeGateway(),
            'expires_at'   => now()->addMinutes(30),
            'meta'         => ['payment_method' => 'card'],
        ]);

        return $order;
    }

    // =========================================================================
    // REJECT QUOTE (customer → terminal)
    // =========================================================================

    public function reject(Quote $quote, ?string $reason = null): void
    {
        DB::transaction(function () use ($quote, $reason) {
            $quote->transitionTo(
                QuoteStatus::REJECTED,
                notes: $reason ?: 'Customer rejected the quotation.',
                changedByType: 'user'
            );

            $quote->update([
                'rejected_at'      => now(),
                'rejection_reason' => $reason,
            ]);
        });

        // Send notification to admin if enabled
        if ($this->notificationSettings->notify_quote_rejected) {
            try {
                $this->notifyAdmin(new QuoteRejectedNotification($quote));
            } catch (\Throwable $e) {
                Log::error('Failed to send QuoteRejectedNotification.', [
                    'quote_id' => $quote->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }

    // =========================================================================
    // CANCEL QUOTE (admin → terminal)
    // =========================================================================

    public function cancel(Quote $quote, ?string $note = null): void
    {
        DB::transaction(function () use ($quote, $note) {
            $quote->transitionTo(
                QuoteStatus::CANCELLED,
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
        if (!$this->quotationSettings->auto_expire_enabled) {
            return 0;
        }

        $expired = Quote::where('status', QuoteStatus::SENT)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expired as $quote) {
            try {
                DB::transaction(function () use ($quote) {
                    $quote->transitionTo(
                        QuoteStatus::EXPIRED,
                        notes: 'Quote expired — no customer response before validity period ended.',
                        changedByType: 'system'
                    );
                });
                $count++;
            } catch (\Throwable $e) {
                Log::error('Failed to expire quotation.', [
                    'quote_id' => $quote->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    // =========================================================================
    // SEND EXPIRING REMINDERS (system → customer, called by scheduled command)
    //
    // Sends reminder emails to customers whose quotes are expiring soon.
    // Only sends one reminder per quote (tracks via reminder_sent_at column).
    // =========================================================================

    public function sendExpiringReminders(): int
    {
        if (!$this->customerNotificationSettings->quote_expiring_reminder) {
            return 0;
        }

        $daysBeforeExpiry = $this->customerNotificationSettings->quote_expiring_days;
        $reminderDate = now()->addDays($daysBeforeExpiry);

        // Find quotes expiring within the reminder window that haven't been reminded
        $quotes = Quote::where('status', QuoteStatus::SENT)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $reminderDate)
            ->where('expires_at', '>', now())
            ->whereNull('reminder_sent_at')
            ->with('user')
            ->get();

        $count = 0;

        foreach ($quotes as $quote) {
            try {
                if ($quote->user) {
                    $quote->user->notify(new QuoteExpiringNotification($quote));
                } else {
                    Notification::route('mail', $quote->customerEmail())
                        ->notify(new QuoteExpiringNotification($quote));
                }

                $quote->update(['reminder_sent_at' => now()]);
                $count++;
            } catch (\Throwable $e) {
                Log::error('Failed to send QuoteExpiringNotification.', [
                    'quote_id' => $quote->id,
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
        $orphaned = Quote::whereNull('user_id')
            ->whereJsonContains('guest_info->email', $email)
            ->get();

        $count = 0;

        foreach ($orphaned as $quote) {
            $quote->update([
                'user_id'    => $userId,
                'guest_info' => null,
            ]);
            $count++;
        }

        return $count;
    }
}
