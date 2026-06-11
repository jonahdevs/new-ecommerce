@props([
    'code',
    'heading',
    'message',
    'title' => null,
    'standalone' => false,
])

@php
    $pageTitle = $title ?? $heading;

    // Treat the request as "admin area" when the URL is under /admin OR the
    // request originated from an admin page. The referer check matters because
    // errors thrown inside Livewire actions are rendered for the /livewire/update
    // endpoint, not the /admin/* URL the user is actually looking at.
    $inAdminArea = request()->is('admin', 'admin/*')
        || str_contains((string) request()->headers->get('referer'), '/admin');

    // Chrome decision:
    //   - 500/503 → standalone (never touch the DB-driven layouts that may be down)
    //   - admin area → admin sidebar shell (the message renders in the main area).
    //     The sidebar-only layout has no auth-dependent topbar, so it also covers
    //     an expired session without leaking the storefront layout.
    //   - everything else → storefront
    $useAdmin = ! $standalone && $inAdminArea;

    $vars = ['code' => $code, 'heading' => $heading, 'message' => $message];
@endphp

@if ($standalone)
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $pageTitle }} · {{ config('app.name', 'Sheffield') }}</title>

        {{-- try/catch so a missing Vite manifest degrades to the inline critical
             styles below instead of throwing a second exception on an error page. --}}
        @php
            try {
                echo app(\Illuminate\Foundation\Vite::class)(['resources/css/app.css']);
            } catch (\Throwable $e) {
                // Fall back to the inline critical styles.
            }
        @endphp

        <style>
            :root { --brand: hsl(354, 68%, 45%); }
            body { margin: 0; font-family: "Public Sans", ui-sans-serif, system-ui, sans-serif; color: #0c1421; background: #fff; }
            .err-standalone { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        </style>
    </head>

    <body class="err-standalone antialiased">
        @include('errors.partials.content', $vars + ['bare' => true])
    </body>

    </html>
@elseif ($useAdmin)
    <x-layouts::app :title="$pageTitle">
        {{-- Plain div slot like every other admin page (no flux:main, which would
             flip the layout into Flux's grid mode and mis-place the content). --}}
        <div class="flex min-h-[80vh] w-full flex-1 items-center justify-center">
            @include('errors.partials.content', $vars + ['bare' => false, 'admin' => true])
        </div>
    </x-layouts::app>
@else
    <x-layouts::storefront :title="$pageTitle">
        @include('errors.partials.content', $vars + ['bare' => false])
    </x-layouts::storefront>
@endif
