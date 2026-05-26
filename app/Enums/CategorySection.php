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
            self::NAVBAR => 'Navbar',
            self::HOME_PAGE_FEATURED => 'Home Page Featured',
            self::FOOTER => 'Footer',
        };
    }
}
