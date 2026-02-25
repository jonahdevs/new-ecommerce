<?php

namespace App\Livewire\Forms\Admin;

use App\Settings\SeoSettings;
use Livewire\Form;

class SeoSettingsForm extends Form
{

    // Meta Tags
    public string $meta_title = '';
    public string $meta_description = '';
    public string $meta_keywords = '';
    public string $canonical_url = '';

    // Open Graph
    public string $og_title = '';
    public string $og_description = '';
    public $og_image = null;
    public ?string $existing_og_image = null;

    // Analytics & Verification
    public string $google_analytics_id = '';
    public string $google_tag_manager_id = '';
    public string $google_site_verification = '';

    // Indexing
    public bool $indexing_enabled = true;

    public function rules(): array
    {
        return [
            'meta_title' => ['required', 'string', 'max:70'],
            'meta_description' => ['required', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:70'],
            'og_description' => ['nullable', 'string', 'max:160'],
            'og_image' => ['nullable', 'image', 'max:2048'],
            'google_analytics_id' => ['nullable', 'string', 'max:50'],
            'google_tag_manager_id' => ['nullable', 'string', 'max:50'],
            'google_site_verification' => ['nullable', 'string', 'max:100'],
            'indexing_enabled' => ['boolean'],
        ];
    }

    public function fromSettings(SeoSettings $settings): void
    {
        $this->meta_title = $settings->meta_title ?? '';
        $this->meta_description = $settings->meta_description ?? '';
        $this->meta_keywords = $settings->meta_keywords ?? '';
        $this->canonical_url = $settings->canonical_url ?? '';
        $this->og_title = $settings->og_title ?? '';
        $this->og_description = $settings->og_description ?? '';
        $this->existing_og_image = $settings->og_image;
        $this->google_analytics_id = $settings->google_analytics_id ?? '';
        $this->google_tag_manager_id = $settings->google_tag_manager_id ?? '';
        $this->google_site_verification = $settings->google_site_verification ?? '';
        $this->indexing_enabled = $settings->indexing_enabled ?? true;
    }

    public function save(SeoSettings $settings): void
    {
        $this->validate();

        $settings->meta_title = $this->meta_title;
        $settings->meta_description = $this->meta_description;
        $settings->meta_keywords = $this->meta_keywords ?: null;
        $settings->canonical_url = $this->canonical_url ?: null;
        $settings->og_title = $this->og_title ?: null;
        $settings->og_description = $this->og_description ?: null;
        $settings->google_analytics_id = $this->google_analytics_id ?: null;
        $settings->google_tag_manager_id = $this->google_tag_manager_id ?: null;
        $settings->google_site_verification = $this->google_site_verification ?: null;
        $settings->indexing_enabled = $this->indexing_enabled;

        if ($this->og_image) {
            $settings->og_image = $this->og_image->store('settings', 'public');
            $this->existing_og_image = $settings->og_image;
            $this->og_image = null;
        }

        $settings->save();
    }

    public function removeOgImage(SeoSettings $settings): void
    {
        $settings->og_image = null;
        $this->existing_og_image = null;
        $settings->save();
    }
}
