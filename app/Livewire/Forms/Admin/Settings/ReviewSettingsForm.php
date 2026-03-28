<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\ReviewSettings;
use Livewire\Form;

class ReviewSettingsForm extends Form
{
    public bool $reviews_enabled = true;
    public bool $require_purchase_to_review = true;
    public bool $auto_approve_reviews = false;
    public bool $allow_anonymous_reviews = false;
    public bool $allow_review_images = true;
    public int $max_review_images = 5;
    public int $min_review_length = 10;

    public function rules(): array
    {
        return [
            'reviews_enabled' => ['boolean'],
            'require_purchase_to_review' => ['boolean'],
            'auto_approve_reviews' => ['boolean'],
            'allow_anonymous_reviews' => ['boolean'],
            'allow_review_images' => ['boolean'],
            'max_review_images' => ['required_if:allow_review_images,true', 'integer', 'min:1', 'max:10'],
            'min_review_length' => ['required', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function fromSettings(ReviewSettings $settings): void
    {
        $this->reviews_enabled = $settings->reviews_enabled;
        $this->require_purchase_to_review = $settings->require_purchase_to_review;
        $this->auto_approve_reviews = $settings->auto_approve_reviews;
        $this->allow_anonymous_reviews = $settings->allow_anonymous_reviews;
        $this->allow_review_images = $settings->allow_review_images;
        $this->max_review_images = $settings->max_review_images;
        $this->min_review_length = $settings->min_review_length;
    }

    public function save(ReviewSettings $settings): void
    {
        $this->validate();

        $settings->reviews_enabled = $this->reviews_enabled;
        $settings->require_purchase_to_review = $this->require_purchase_to_review;
        $settings->auto_approve_reviews = $this->auto_approve_reviews;
        $settings->allow_anonymous_reviews = $this->allow_anonymous_reviews;
        $settings->allow_review_images = $this->allow_review_images;
        $settings->max_review_images = $this->max_review_images;
        $settings->min_review_length = $this->min_review_length;

        $settings->save();
    }
}
