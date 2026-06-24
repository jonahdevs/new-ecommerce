<?php

use App\Http\Middleware\BlockBannedIp;
use App\Http\Middleware\ConfigureSeo;
use App\Http\Middleware\EnsureIsCustomer;
use App\Http\Middleware\EnsureIsStaffMember;
use App\Http\Middleware\EnsureStoreNotInMaintenance;
use App\Http\Middleware\ForbidBannedUser;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'staff' => EnsureIsStaffMember::class,
            'customer' => EnsureIsCustomer::class,
        ]);

        // Payment provider webhooks are server-to-server and carry no CSRF token.
        $middleware->validateCsrfTokens(except: [
            'api/webhooks/mpesa',
            'api/webhooks/stripe',
            'api/webhooks/paystack',
        ]);

        // Apply store-wide SEO defaults before controllers/Livewire run.
        $middleware->web(append: [
            AuthenticateSession::class,
            BlockBannedIp::class,
            ConfigureSeo::class,
            EnsureStoreNotInMaintenance::class,
            ForbidBannedUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log InnoDB lock wait timeouts (1205) and deadlocks (1213) that survive
        // all DB::transaction() retries to the dedicated db channel so they appear
        // in storage/logs/db-*.log alongside slow query entries.
        $exceptions->report(function (QueryException $e): void {
            $code = (int) $e->getCode();

            if (in_array($code, [1205, 1213], true)) {
                Log::channel('db')->error($code === 1205 ? 'Lock wait timeout' : 'Deadlock', [
                    'code' => $code,
                    'sql' => $e->getSql(),
                    'url' => request()?->fullUrl(),
                    'message' => $e->getMessage(),
                ]);
            }
        });
    })->create();
