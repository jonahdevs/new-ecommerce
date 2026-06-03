<?php

namespace App\Rules;

use App\Settings\IntegrationSettings;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;

class Recaptcha implements ValidationRule
{
    public function __construct(private readonly string $action = 'submit') {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isConfigured()) {
            return;
        }

        $result = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ])->json();

        if (! ($result['success'] ?? false) || ($result['score'] ?? 0) < 0.5) {
            $fail(__('Security verification failed. Please try again.'));
        }
    }

    public static function isConfigured(): bool
    {
        $siteKey = app(IntegrationSettings::class)->recaptcha_site_key
            ?: config('services.recaptcha.site_key');

        return filled($siteKey) && filled(config('services.recaptcha.secret'));
    }
}
