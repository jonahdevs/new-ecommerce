<?php

namespace App\Providers;

use App\Events\PaymentConfirmed;
use App\Listeners\SendNewOrderNotification;
use App\Listeners\SendNewUserNotification;
use App\Listeners\SendWelcomeEmail;
use App\Listeners\SyncCartOnLogin;
use App\Listeners\SyncRecentViewedOnLogin;
use App\Listeners\SyncWishlistOnLogin;
use App\Models\Review;
use App\Observers\ReviewObserver;
use App\Services\CartService;
use App\Services\CompareService;
use App\Services\WishlistService;
use App\Settings\GeneralSettings;
use App\Settings\SeoSettings;
use App\View\Composers\FooterComposer;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CartService::class);
        $this->app->singleton(WishlistService::class);
        $this->app->singleton(CompareService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        if (request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }
        Event::listen(Login::class, SyncCartOnLogin::class);
        Event::listen(Login::class, SyncWishlistOnLogin::class);
        Event::listen(Login::class, SyncRecentViewedOnLogin::class);
        Event::listen(PaymentConfirmed::class, SendNewOrderNotification::class);
        Event::listen(Registered::class, SendNewUserNotification::class);
        Event::listen(Registered::class, SendWelcomeEmail::class);

        Review::observe(ReviewObserver::class);

        OpenGraph::addProperty('locale', 'en_KE');

        // Set SEO and OG defaults from settings, falling back to config values.
        // Individual pages override these in their mount() / render() hooks.
        try {
            $general = app(GeneralSettings::class);
            $seo = app(SeoSettings::class);

            OpenGraph::setSiteName($general->store_name ?: config('app.name'));

            if ($seo->meta_title) {
                SEOMeta::setTitle($seo->meta_title);
            }

            if ($seo->meta_description) {
                SEOMeta::setDescription($seo->meta_description);
            }

            if ($seo->meta_keywords) {
                SEOMeta::setKeywords(array_map('trim', explode(',', $seo->meta_keywords)));
            }

            if ($seo->og_image) {
                OpenGraph::setImage($seo->og_image);
            }

            SEOMeta::setRobots($seo->robots_indexing ? 'all' : 'noindex,nofollow');
        } catch (\Throwable) {
            // Settings table may not exist during migrations or console commands.
            OpenGraph::setSiteName(config('app.name'));
        }

        $this->configureDefaults();

        View::composer('components.footer', FooterComposer::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
