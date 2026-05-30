<?php

namespace App\Enums;

enum ProductStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case SCHEDULED = 'scheduled';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::SCHEDULED => 'Scheduled',
            self::ARCHIVED => 'Archived',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT => 'zinc',
            self::PUBLISHED => 'green',
            self::SCHEDULED => 'blue',
            self::ARCHIVED => 'red',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'text-zinc-500',
            self::PUBLISHED => 'text-emerald-600',
            self::SCHEDULED => 'text-blue-600',
            self::ARCHIVED => 'text-red-500',
        };
    }
}
