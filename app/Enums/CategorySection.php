<?php

namespace App\Enums;

enum CategorySection: string
{
    case Navbar = 'navbar';
    case HomepageFeatured = 'homepage_featured';
    case Footer = 'footer';

    public function label(): string
    {
        return match ($this) {
            self::Navbar => 'Navigation Bar',
            self::HomepageFeatured => 'Homepage Featured',
            self::Footer => 'Footer',
        };
    }
}
