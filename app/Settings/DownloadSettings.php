<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class DownloadSettings extends Settings
{
    public int $default_download_limit;

    public int $default_expiry_days;

    public bool $secure_downloads;

    public static function group(): string
    {
        return 'downloads';
    }
}
