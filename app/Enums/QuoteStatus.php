<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case AWAITING_APPROVAL = 'awaiting_approval';
    case APPROVED = 'approved';
    case DECLINED = 'declined';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::AWAITING_APPROVAL => 'Awaiting your approval',
            self::APPROVED => 'Approved',
            self::DECLINED => 'Declined',
            self::EXPIRED => 'Expired',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT => 'zinc',
            self::SENT => 'blue',
            self::AWAITING_APPROVAL => 'amber',
            self::APPROVED => 'green',
            self::DECLINED => 'red',
            self::EXPIRED => 'zinc',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'text-ink-3',
            self::SENT => 'text-blue-600',
            self::AWAITING_APPROVAL => 'text-brand-500',
            self::APPROVED => 'text-emerald-600',
            self::DECLINED => 'text-red-500',
            self::EXPIRED => 'text-ink-4',
        };
    }
}
