<?php

use App\Http\Middleware\EnsureUserIsCustomer;
use App\Http\Middleware\EnsureUserIsStaff;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'cart_not_empty' => \App\Http\Middleware\EnsureCartIsNotEmpty::class,
            'staff'    => EnsureUserIsStaff::class,
            'customer' => EnsureUserIsCustomer::class,
        ]);

        // Use custom CSRF middleware to exclude payment callbacks
        $middleware->validateCsrfTokens(except: [
            '/webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $e, Request $request) {
            $status = $e->getStatusCode();
            $view   = "errors.{$status}";

            if (in_array($status, [403, 404, 419, 500, 503]) && view()->exists($view)) {
                view()->share('isErrorPage', true);
                return response()->view($view, ['exception' => $e], $status);
            }
        });
    })->create();
