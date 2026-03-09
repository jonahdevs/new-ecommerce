<?php

namespace App\Enums;

enum ProductVisibility: string
{
    case PUBLIC = 'public'; // Everyone can see it, appears in catalog & search
    case CATALOG = 'catalog'; // Appears in shop/category pages but NOT in search results
    case SEARCH = 'search'; // Appears in search results but NOT in catalog/category pages
    case HIDDEN = 'hidden'; // Only accessible via direct URL, invisible everywhere else

    public function label()
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::CATALOG => 'Catalog',
            self::SEARCH => 'Search',
            self::HIDDEN => 'Hidden',
        };
    }
}
