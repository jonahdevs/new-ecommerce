<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ReviewSettings extends Settings
{
    public bool $reviews_enabled;

    public bool $require_verified_purchase;

    public bool $auto_approve;

    public static function group(): string
    {
        return 'reviews';
    }
}
