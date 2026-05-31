<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SocialSettings extends Settings
{
    public ?string $og_image_path;

    public string $twitter_handle;

    public string $facebook_url;

    public string $instagram_url;

    public string $x_url;

    public string $linkedin_url;

    public string $youtube_url;

    public string $tiktok_url;

    public string $whatsapp_number;

    public static function group(): string
    {
        return 'social';
    }
}
