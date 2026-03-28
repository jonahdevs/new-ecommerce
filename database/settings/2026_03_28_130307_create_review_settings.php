<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('reviews.reviews_enabled', true);
        $this->migrator->add('reviews.require_purchase_to_review', true);
        $this->migrator->add('reviews.auto_approve_reviews', false);
        $this->migrator->add('reviews.allow_anonymous_reviews', false);
        $this->migrator->add('reviews.allow_review_images', true);
        $this->migrator->add('reviews.max_review_images', 5);
        $this->migrator->add('reviews.min_review_length', 10);
    }
};
