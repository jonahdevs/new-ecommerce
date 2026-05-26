<?php

namespace App\Enums;

enum ProductType: string
{
    case SIMPLE = 'simple';
    case VARIABLE = 'variable';
    case VIRTUAL = 'virtual';
    case DOWNLOADABLE = 'downloadable';
    case GROUPED = 'grouped';
    case BUNDLE = 'bundled';
}
