<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case PENDING = 'pending';       // Customer submitted, awaiting admin pricing
    case SENT = 'sent';             // Admin priced and sent to customer
    case ACCEPTED = 'accepted';     // Customer accepted, order created
    case REJECTED = 'rejected';     // Customer rejected
    case EXPIRED = 'expired';       // Validity period passed without response
    case CANCELLED = 'cancelled';   // Admin cancelled

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Review',
            self::SENT => 'Quote Sent',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::SENT => 'blue',
            self::ACCEPTED => 'green',
            self::REJECTED => 'red',
            self::EXPIRED => 'zinc',
            self::CANCELLED => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::SENT => 'paper-airplane',
            self::ACCEPTED => 'check-circle',
            self::REJECTED => 'x-circle',
            self::EXPIRED => 'exclamation-circle',
            self::CANCELLED => 'x-mark',
        };
    }

    /**
     * Valid transitions from this status.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::SENT, self::CANCELLED],
            self::SENT => [self::ACCEPTED, self::REJECTED, self::EXPIRED, self::CANCELLED],
            self::ACCEPTED => [],
            self::REJECTED => [],
            self::EXPIRED => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $new): bool
    {
        return in_array($new, $this->allowedTransitions());
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::ACCEPTED, self::REJECTED, self::EXPIRED, self::CANCELLED]);
    }
}
