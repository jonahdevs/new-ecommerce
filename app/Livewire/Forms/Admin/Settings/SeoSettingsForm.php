<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\SeoSettings;
use Livewire\Form;

class SeoSettingsForm extends Form
{
    public string $meta_title = '';

    public string $meta_description = '';

    public string $meta_keywords = '';

    public string $og_image = '';

    public bool $robots_indexing = true;

    public bool $sitemap_enabled = true;

    public string $google_site_verification = '';

    public string $canonical_url = '';

    public function rules(): array
    {
        return [
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'og_image' => ['nullable', 'string', 'max:255'],
            'robots_indexing' => ['boolean'],
            'sitemap_enabled' => ['boolean'],
            'google_site_verification' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function fromSettings(SeoSettings $settings): void
    {
        $this->meta_title = $settings->meta_title ?? '';
        $this->meta_description = $settings->meta_description ?? '';
        $this->meta_keywords = $settings->meta_keywords ?? '';
        $this->og_image = $settings->og_image ?? '';
        $this->robots_indexing = $settings->robots_indexing;
        $this->sitemap_enabled = $settings->sitemap_enabled;
        $this->google_site_verification = $settings->google_site_verification ?? '';
        $this->canonical_url = $settings->canonical_url ?? '';
    }

    public function save(SeoSettings $settings): void
    {
        $this->validate();

        $settings->meta_title = $this->meta_title ?: null;
        $settings->meta_description = $this->meta_description ?: null;
        $settings->meta_keywords = $this->meta_keywords ?: null;
        $settings->og_image = $this->og_image ?: null;
        $settings->robots_indexing = $this->robots_indexing;
        $settings->sitemap_enabled = $this->sitemap_enabled;
        $settings->google_site_verification = $this->google_site_verification ?: null;
        $settings->canonical_url = $this->canonical_url ?: null;

        $settings->save();
    }
}
