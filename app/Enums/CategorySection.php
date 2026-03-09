<?php

namespace App\Enums;

enum CategorySection: string
{
    case NAVBAR = 'navbar';
    case HOME_PAGE_FEATURED = 'homepage_featured';
    case FOOTER = 'footer';

    public function label(): string
    {
        return match ($this) {
            self::NAVBAR => 'Navigation Bar',
            self::HOME_PAGE_FEATURED => 'Homepage Featured',
            self::FOOTER => 'Footer',
        };
    }
}
