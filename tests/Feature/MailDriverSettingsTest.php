<?php

use App\Providers\AppServiceProvider;
use App\Settings\EmailApiSettings;
use App\Settings\EmailSettings;
use Illuminate\Support\Facades\Mail;

/**
 * Re-run the provider's protected mail configuration against the current
 * settings, mirroring what happens during application boot.
 */
function applyMailConfig(): void
{
    $provider = new AppServiceProvider(app());
    $method = new ReflectionMethod($provider, 'configureMail');
    $method->setAccessible(true);
    $method->invoke($provider);
}

it('applies the Amazon SES driver and credentials from settings', function () {
    app(EmailSettings::class)->fill(['mail_driver' => 'ses'])->save();
    app(EmailApiSettings::class)->fill([
        'ses_key' => 'AKIAEXAMPLE',
        'ses_secret' => 'super-secret',
        'ses_region' => 'eu-west-1',
    ])->save();

    applyMailConfig();

    expect(config('mail.default'))->toBe('ses')
        ->and(config('services.ses.key'))->toBe('AKIAEXAMPLE')
        ->and(config('services.ses.secret'))->toBe('super-secret')
        ->and(config('services.ses.region'))->toBe('eu-west-1');
});

it('leaves config defaults untouched when a credential is blank', function () {
    config(['services.ses.key' => 'env-fallback-key']);

    app(EmailApiSettings::class)->fill(['ses_key' => null])->save();

    applyMailConfig();

    expect(config('services.ses.key'))->toBe('env-fallback-key');
});

it('can build the transport for every selectable mail driver', function (string $driver) {
    // Minimal credentials so the API transports can be instantiated.
    config([
        'services.ses' => ['key' => 'k', 'secret' => 's', 'region' => 'us-east-1'],
        'services.mailgun' => ['domain' => 'mg.example.com', 'secret' => 's', 'endpoint' => 'api.mailgun.net', 'scheme' => 'https'],
        'services.postmark.key' => 'k',
        'services.resend.key' => 'k',
    ]);

    expect(fn () => Mail::mailer($driver)->getSymfonyTransport())->not->toThrow(Exception::class);
})->with(['smtp', 'ses', 'mailgun', 'postmark', 'resend', 'log']);
