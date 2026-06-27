<?php

namespace App\Providers;

use App\Events\LowStockDetected;
use App\Http\Middleware\ValidateRecaptcha;
use App\Listeners\HandleLowStockAlert;
use App\Listeners\SendBanNotification;
use App\Listeners\SyncCartOnLogin;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\AiManager;
use App\Services\Ai\ChatAssistant;
use App\Services\Ai\Tools\OrderStatusTool;
use App\Services\Ai\Tools\ProductSearchTool;
use App\Services\Mpesa\DarajaClient;
use App\Services\PaymentCredentials;
use App\Settings\BrandingSettings;
use App\Settings\ChatbotSettings;
use App\Settings\EmailApiSettings;
use App\Settings\EmailSettings;
use App\Settings\LocalizationSettings;
use App\Settings\SecuritySettings;
use App\Support\ActivitySource;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Cog\Laravel\Ban\Events\ModelWasBanned;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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

        // Chatbot: resolve the AiChatProvider contract to whichever provider
        // config/ai.php selects (Groq by default). Flip AI_PROVIDER to switch.
        $this->app->singleton(AiManager::class);
        $this->app->bind(AiChatProvider::class, fn ($app): AiChatProvider => $app->make(AiManager::class)->provider());

        // The assistant wraps the provider with the tools it may call to read
        // live store data. Each tool is gated by an admin toggle (ChatbotSettings).
        $this->app->bind(ChatAssistant::class, function ($app): ChatAssistant {
            $productSearch = true;
            $orderLookup = true;

            try {
                $settings = $app->make(ChatbotSettings::class);
                $productSearch = $settings->product_search_enabled;
                $orderLookup = $settings->order_lookup_enabled;
            } catch (\Throwable) {
                // Settings unavailable — default both tools on.
            }

            $tools = array_filter([
                $productSearch ? $app->make(ProductSearchTool::class) : null,
                $orderLookup ? $app->make(OrderStatusTool::class) : null,
            ]);

            return new ChatAssistant($app->make(AiChatProvider::class), array_values($tools));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureDatabase();
        $this->configureSuperAdmin();
        $this->configureRecaptcha();
        $this->configureActivitySource();
        $this->configureMail();
        $this->shareEmailBranding();

        // Merge the guest cart into the user's persisted cart on every login,
        // registration, 2FA and passkey auth (all dispatch the Login event).
        Event::listen(Login::class, SyncCartOnLogin::class);
        Event::listen(LowStockDetected::class, HandleLowStockAlert::class);

        // Email the customer a suspension notice whenever they are banned.
        Event::listen(ModelWasBanned::class, SendBanNotification::class);
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

        // Global password policy. Minimum length is fixed here (not admin-configurable);
        // production additionally enforces complexity and breach checks.
        Password::defaults(function (): Password {
            $rule = Password::min(8);

            return app()->isProduction()
                ? $rule->mixedCase()->letters()->numbers()->symbols()->uncompromised()
                : $rule;
        });

        $this->configureSessionLifetime();
        $this->configureTimezone();
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

    /**
     * Tune per-session InnoDB settings for HTTP requests. Queue workers and
     * Artisan commands keep the MySQL global default (50 s) since long-running
     * jobs legitimately wait longer for locks.
     */
    protected function configureDatabase(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            DB::statement('SET SESSION innodb_lock_wait_timeout = 5');
        } catch (\Throwable) {
            // DB unavailable during early boot (e.g. pre-migration).
        }

        // Log any query that takes longer than 500 ms to the dedicated db channel
        // so slow queries surface without needing MySQL slow-query-log access.
        DB::whenQueryingForLongerThan(500, function (Connection $connection, QueryExecuted $event): void {
            Log::channel('db')->warning('Slow query', [
                'ms' => round($event->time, 2),
                'sql' => $event->sql,
                'bindings' => $event->bindings,
            ]);
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

    protected function configureTimezone(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $timezone = app(LocalizationSettings::class)->timezone;

            if ($timezone && in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
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
