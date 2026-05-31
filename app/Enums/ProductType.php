<?php

namespace App\Enums;

enum ProductType: string
{
    case SIMPLE = 'simple';
    case VARIABLE = 'variable';
    case GROUPED = 'grouped';
    case BUNDLE = 'bundled';
}
