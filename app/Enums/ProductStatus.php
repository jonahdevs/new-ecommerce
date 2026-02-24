<?php

namespace App\Enums;

enum ProductStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label()
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SCHEDULED => 'Scheduled',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    public function color()
    {
        return match ($this) {
            self::DRAFT => 'zinc',
            self::SCHEDULED => 'purple',
            self::PUBLISHED => 'green',
            self::ARCHIVED => 'amber',
        };
    }

    public function icon()
    {
        return match ($this) {
            self::DRAFT => 'pencil-square',
            self::SCHEDULED => 'clock',
            self::PUBLISHED => 'check-circle',
            self::ARCHIVED => 'clipboard-document-check',
        };
    }
}
