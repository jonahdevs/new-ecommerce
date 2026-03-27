<?php

namespace App\Providers;

use App\Services\Sap\SapIntegrationService;
use Illuminate\Support\ServiceProvider;

class SapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind SapIntegrationService as a singleton so the session cookie
        // is shared across all injections within a single queue job execution.
        // Each job process gets its own singleton — no cross-job session leaking.
        $this->app->singleton(SapIntegrationService::class, function () {
            return new SapIntegrationService(
                baseUrl: config('sap.service_layer_url'),
                companyDb: config('sap.company_db'),
                username: config('sap.username'),
                password: config('sap.password'),
                sessionTimeoutMinutes: (int) config('sap.session_timeout', 30),
            );
        });
    }
}
