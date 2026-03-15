<?php

namespace App\Enums;

// ==============================================
// Order Status
//
// Two document lifecycles share this single enum.
// The document_type column on the Order model determine which path applies
//
// SALES ORDER Lifecycle:
// pending -> confirmed -> processing -> shipped -> delivered -> returned -> cancelled (from pending or confirmed or processing)
//
// QUOTATION lifecycle (both delivery and product quotation_type):
// pending_quote -> quote_sent -> quote_accepted  (terminal - SO created separately) -> quote_rejected (terminal) -> quote_expired (terminal -scheduled job) -> cancelled (terminal - admin discard before sending)
//
// Note on QUOTE_ACCEPTED:
// This status is terminal on the quotation record it self. A new Order row (document_type=sale_order) is created by
// QuotationService::convert() and begins at PENDING.
// The quotation is never mutated further - it remain the permanent history document (mirrors SAP document flow)

// Note on QUOTE_EXPIRED:
// Driven by a scheduled job (e.g. ExpireQuotations command) that check orders where:
// document_type = quotation
// status IN (pending_quote, quote_sent)
// expires_at < now()
// And fires transitionTo(QUOTE_EXPIRED) with changedByType='system'
// =============================================

enum OrdersStatus: string
{
    // ===============================================
    // Sale order statuses
    // ===============================================
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case RETURNED = 'returned';

    // =============================================
    // Quotation statuses
    // =============================================
    // Quotation has been created (by checkout - not yet priced)
    // This is where both delivery and product quotations start
    case PENDING_QUOTE = 'pending_quote';
    // admin has priced the quotation and sent it to the customer.
    // quoted_at is stamped when this transition fires
    // expires_at is set at this point if not already set
    case QUOTE_SENT = 'quote_sent';
    // Customer accepted the quote before expiry.
    // Terminal on the quotation record - QuotationService::convert()
    // Creates a new sales order immediately after transition.
    case QUOTE_ACCEPTED = 'quote_accepted';
    // Customer explicitly rejected the quote
    // Terminal. No further action needed
    case QUOTE_REJECTED = 'quote_rejected';
    // Expires_at was breached without customer response.
    // Set by the ExpireQuotations scheduled command, changedByType='system'
    // Terminal. Admin may choose to re-quote (create a new quotation).
    case QUOTE_EXPIRED = 'quote_expired';


    // =============================================
    // LABELS
    // =============================================
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::PROCESSING => 'Processing',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::RETURNED => 'Returned',
            self::PENDING_QUOTE => 'Pending Quote',
            self::QUOTE_SENT => 'Quote Sent',
            self::QUOTE_ACCEPTED => 'Quote Accepted',
            self::QUOTE_REJECTED => 'Quote Rejected',
            self::QUOTE_EXPIRED => 'Quote Expired',
        };
    }


    // =============================================
    // COLORS
    // Matches Flux UI / Tailwind color names used in admin views
    // =============================================

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::CONFIRMED => 'blue',
            self::PROCESSING => 'purple',
            self::SHIPPED => 'indigo',
            self::DELIVERED => 'emerald',
            self::CANCELLED => 'rose',
            self::RETURNED => 'orange',
            self::PENDING_QUOTE => 'yellow',
            self::QUOTE_SENT => 'cyan',
            self::QUOTE_ACCEPTED => 'teal',
            self::QUOTE_REJECTED => 'red',
            self::QUOTE_EXPIRED => 'zinc',
        };
    }

    // =============================================
    // ICONS
    // Matches Flux/Heroicons/Lucide icons names used in admin and customer view
    // =============================================


    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::CONFIRMED => 'check-badge',
            self::PROCESSING => 'loader-circle',
            self::SHIPPED => 'truck',
            self::DELIVERED => 'package-check',
            self::CANCELLED => 'x-circle',
            self::RETURNED => 'rotate-ccw',
            self::PENDING_QUOTE => 'tag',
            self::QUOTE_SENT => 'send',
            self::QUOTE_ACCEPTED => 'check-circle',
            self::QUOTE_REJECTED => 'octagon-x',
            self::QUOTE_EXPIRED => 'clock-alert',
        };
    }

    // =============================================
    // TRANSITION GUARDS
    // =============================================

    public function canTransitionTo(self $new): bool
    {
        return in_array($new, $this->allowedTransitions());
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
                // ===========================================
                // Sales order transitions
                // ===========================================
            self::PENDING => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::DELIVERED, self::RETURNED],
            self::DELIVERED => [self::RETURNED],
            self::CANCELLED => [],
            self::RETURNED => [],

                // ==========================================
                // Quotation transitions
                //
                // PENDING_QUOTE -> QUOTE_SENT:
                // Admin has priced and is sending to customer
                //
                // PENDING_QUOTE -> CANCELLED
                // Admin discards before ever sending (e.g. invalid request).
                //
                // QUOTE_SENT -> QUOTE_ACCEPTED:
                // Customer clicks "Accept quote" in their portal.
                // QuotationService::convert() fires immediately after.
                //
                // QUOTE_SENT -> QUOTE_REJECTED:
                // Customer clicks "Reject quote" in their portal
                //
                // QUOTE_SENT -> QUOTE_EXPIRED:
                // ExpiredQuotations command - expires_at < now()
                // changedByType will be 'system' in status history.
                //
                // QUOTE_ACCEPTED / QUOTE_REJECTED / QUOTE_EXPIRED -> []
                // All terminal. A new quotation must be created to re-engage
                // ==========================================

            self::PENDING_QUOTE => [self::QUOTE_SENT, self::CANCELLED],
            self::QUOTE_SENT => [self::QUOTE_ACCEPTED, self::QUOTE_REJECTED, self::QUOTE_EXPIRED],
            self::QUOTE_ACCEPTED => [],
            self::QUOTE_REJECTED => [],
            self::QUOTE_EXPIRED => [],
        };
    }

    // =================================================
    // HELPER PREDICATES
    // Useful in Blade views, Livewire components, and policies without importing the enum cases directly
    // =================================================

    // True for all quotation lifecycle statuses
    public function isQuotationStatus(): bool
    {
        return in_array($this, [
            self::PENDING_QUOTE,
            self::QUOTE_SENT,
            self::QUOTE_ACCEPTED,
            self::QUOTE_REJECTED,
            self::QUOTE_EXPIRED,
        ]);
    }

    // True for status that are permanetly terminal (no further transitions)
    public function isTerminal(): bool
    {
        return empty($this->allowedTransitions());
    }

    // True when the quotation is still actionable by the customer
    public function isAwaitingCustomerResponse(): bool
    {
        return $this === self::QUOTE_SENT;
    }

    // True when the quotation is still actionable by admin
    public function isAwaitingAdminAction(): bool
    {
        return $this === self::PENDING_QUOTE;
    }
}
