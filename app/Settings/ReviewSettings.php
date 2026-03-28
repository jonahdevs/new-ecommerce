<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ReviewSettings extends Settings
{
    public bool $reviews_enabled;
    public bool $require_purchase_to_review;  // only verified buyers can review
    public bool $auto_approve_reviews;        // false = manual moderation
    public bool $allow_anonymous_reviews;
    public bool $allow_review_images;
    public int $max_review_images;           // e.g. 5
    public int $min_review_length;           // minimum characters, 0 = no limit

    public static function group(): string
    {
        return 'reviews';
    }
}
