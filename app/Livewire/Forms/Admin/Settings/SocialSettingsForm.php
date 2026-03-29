<?php

namespace App\Livewire\Forms\Admin\Settings;

use App\Settings\SocialSettings;
use Livewire\Form;

class SocialSettingsForm extends Form
{
    public string $facebook_url = '';
    public string $instagram_url = '';
    public string $twitter_url = '';
    public string $tiktok_url = '';
    public string $youtube_url = '';
    public string $linkedin_url = '';
    public string $whatsapp_number = '';

    public function rules(): array
    {
        return [
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function fromSettings(SocialSettings $settings): void
    {
        $this->facebook_url = $settings->facebook_url ?? '';
        $this->instagram_url = $settings->instagram_url ?? '';
        $this->twitter_url = $settings->twitter_url ?? '';
        $this->tiktok_url = $settings->tiktok_url ?? '';
        $this->youtube_url = $settings->youtube_url ?? '';
        $this->linkedin_url = $settings->linkedin_url ?? '';
        $this->whatsapp_number = $settings->whatsapp_number ?? '';
    }

    public function save(SocialSettings $settings): void
    {
        $this->validate();

        $settings->facebook_url = $this->facebook_url ?: null;
        $settings->instagram_url = $this->instagram_url ?: null;
        $settings->twitter_url = $this->twitter_url ?: null;
        $settings->tiktok_url = $this->tiktok_url ?: null;
        $settings->youtube_url = $this->youtube_url ?: null;
        $settings->linkedin_url = $this->linkedin_url ?: null;
        $settings->whatsapp_number = $this->whatsapp_number ?: null;

        $settings->save();
    }
}
