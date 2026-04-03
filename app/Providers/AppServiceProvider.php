<?php

namespace App\Providers;

use App\Events\PaymentConfirmed;
use App\Listeners\SendNewOrderNotification;
use App\Listeners\SendNewUserNotification;
use App\Listeners\SyncCartOnLogin;
use App\Listeners\SyncRecentViewedOnLogin;
use App\Listeners\SyncWishlistOnLogin;
use App\Models\Review;
use App\Observers\ReviewObserver;
use App\Services\CartService;
use App\Services\CompareService;
use App\Services\WishlistService;
use App\View\Composers\FooterComposer;
use Artesaos\SEOTools\Facades\OpenGraph;
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

        Review::observe(ReviewObserver::class);

        OpenGraph::addProperty('locale', 'en_KE');
        OpenGraph::setSiteName(config('app.name'));

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
