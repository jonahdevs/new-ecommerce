<?php

namespace App\Enums;

enum ProductType: string
{
    case SIMPLE = 'simple';
    case VARIABLE = 'variable';
    case GROUPED = 'grouped';

    public function label(): string
    {
        return match ($this) {
            self::SIMPLE => 'Simple Product',
            self::VARIABLE => 'Variable Product',
            self::GROUPED => 'Grouped Product',
        };
    }
}
