<?php

namespace App\Enums;

enum ProductVisibility: string
{
    case VISIBLE = 'visible';
    case HIDDEN = 'hidden';
    case CATALOG = 'catalog';
    case SEARCH = 'search';

    public function label(): string
    {
        return match ($this) {
            self::VISIBLE => 'Visible',
            self::HIDDEN => 'Hidden',
            self::CATALOG => 'Catalog Only',
            self::SEARCH => 'Search Only',
        };
    }
}
