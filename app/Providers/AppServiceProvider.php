<?php

namespace App\Providers;

use App\Http\Middleware\ValidateRecaptcha;
use App\Services\Mpesa\DarajaClient;
use App\Settings\SecuritySettings;
use App\Support\ActivitySource;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
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
        $this->app->bind(DarajaClient::class, fn (): DarajaClient => DarajaClient::fromConfig());
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
}
