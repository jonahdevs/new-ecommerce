<?php

namespace App\Providers;

use App\Http\Middleware\ValidateRecaptcha;
use App\Listeners\SyncCartOnLogin;
use App\Services\Mpesa\DarajaClient;
use App\Services\PaymentCredentials;
use App\Settings\BrandingSettings;
use App\Settings\EmailApiSettings;
use App\Settings\EmailSettings;
use App\Settings\SecuritySettings;
use App\Support\ActivitySource;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DarajaClient::class, fn ($app): DarajaClient => new DarajaClient(
            $app->make(PaymentCredentials::class)->mpesaConfig()
        ));
        $this->app->singleton(Money::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSuperAdmin();
        $this->configureRecaptcha();
        $this->configureActivitySource();
        $this->configureMail();
        $this->shareEmailBranding();

        // Merge the guest cart into the user's persisted cart on every login,
        // registration, 2FA and passkey auth (all dispatch the Login event).
        Event::listen(Login::class, SyncCartOnLogin::class);
    }

    /**
     * Share the admin-configured brand logo and favicon with every email view
     * so the mailer mirrors the storefront. Both resolve to absolute URLs
     * (required for email clients) and fall back to the bundled assets when no
     * custom upload exists. Runs at render time, so settings are safe to read.
     */
    protected function shareEmailBranding(): void
    {
        View::composer(['mails.*', 'emails.*'], function ($view): void {
            $branding = app(BrandingSettings::class);

            $view->with('emailLogoUrl', $branding->logo_path
                ? Storage::disk('public')->url($branding->logo_path)
                : asset('logo.png'));

            // Reversed (white) logo for the navy header band. Only the bundled
            // Sheffield logo ships a reversed variant; a custom uploaded logo
            // has none, so this resolves to null and the header falls back to
            // the colour logo on a white pill.
            $view->with('emailLogoInverseUrl', $branding->logo_path
                ? null
                : asset('logo-email-inverse.png'));

            $view->with('emailFaviconUrl', $branding->favicon_path
                ? Storage::disk('public')->url($branding->favicon_path)
                : asset('favicon.png'));
        });
    }

    /**
     * Stamp the originating source (e.g. "SAP sync") onto activity-log entries
     * that are created without an authenticated causer.
     */
    protected function configureActivitySource(): void
    {
        Activity::creating(function (Activity $activity): void {
            if ($activity->causer_id === null && ($source = ActivitySource::current()) !== null) {
                $properties = $activity->properties ?? collect();
                $activity->properties = $properties->put('source', $source);
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    /**
     * Grant super-admin users all abilities without needing explicit permissions.
     * Returns null (not false) for other users so normal gate checks still run.
     */
    protected function configureSuperAdmin(): void
    {
        Gate::before(function ($user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Minimum password length is admin-configurable. The closure runs at
        // validation time (not boot), so it reads the current setting safely.
        Password::defaults(function (): Password {
            $rule = Password::min(app(SecuritySettings::class)->min_password_length);

            return app()->isProduction()
                ? $rule->mixedCase()->letters()->numbers()->symbols()->uncompromised()
                : $rule;
        });

        $this->configureSessionLifetime();
    }

    /**
     * Apply the admin-configurable session lifetime. Guarded so early boot and
     * fresh migrations (settings table not yet present) never error.
     */
    protected function configureRecaptcha(): void
    {
        // Attach reCAPTCHA validation to the forgot-password route after all routes are registered.
        $this->app->booted(function () {
            $route = Route::getRoutes()->getByName('password.email');
            if ($route) {
                $route->middleware(ValidateRecaptcha::class.':forgot_password');
            }
        });
    }

    protected function configureSessionLifetime(): void
    {
        try {
            if (Schema::hasTable('settings')) {
                config(['session.lifetime' => app(SecuritySettings::class)->session_lifetime]);
            }
        } catch (\Throwable) {
            // Settings unavailable (e.g. mid-migration) — keep the config default.
        }
    }

    /**
     * Apply the admin-configurable mail driver and provider credentials. Only
     * non-empty settings override config, so anything left blank in the admin
     * falls back to the .env defaults. Guarded so early boot and fresh
     * migrations (settings table not yet present) never error.
     */
    protected function configureMail(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $email = app(EmailSettings::class);
            $api = app(EmailApiSettings::class);

            $overrides = array_filter([
                'mail.default' => $email->mail_driver ?: null,
                'mail.from.address' => $email->from_address ?: null,
                'mail.from.name' => $email->from_name ?: null,

                'mail.mailers.smtp.host' => $api->smtp_host ?: null,
                'mail.mailers.smtp.port' => $api->smtp_port ?: null,
                'mail.mailers.smtp.scheme' => $api->smtp_encryption && $api->smtp_encryption !== 'none' ? $api->smtp_encryption : null,
                'mail.mailers.smtp.username' => $api->smtp_username ?: null,
                'mail.mailers.smtp.password' => $api->smtp_password ?: null,

                'services.ses.key' => $api->ses_key ?: null,
                'services.ses.secret' => $api->ses_secret ?: null,
                'services.ses.region' => $api->ses_region ?: null,

                'services.mailgun.domain' => $api->mailgun_domain ?: null,
                'services.mailgun.secret' => $api->mailgun_secret ?: null,

                'services.postmark.key' => $api->postmark_token ?: null,

                'services.resend.key' => $api->resend_key ?: null,
            ], fn ($value): bool => $value !== null);

            if ($overrides !== []) {
                config($overrides);
            }
        } catch (\Throwable) {
            // Settings unavailable (e.g. mid-migration) — keep the config defaults.
        }
    }
}
