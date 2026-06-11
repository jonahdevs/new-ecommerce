@php
    /**
     * Inner error content shared by every error page, whether it is wrapped in
     * storefront/admin chrome or rendered standalone.
     *
     * @var int|string $code
     * @var string     $heading
     * @var string     $message
     * @var bool       $bare    Render the logo (true when there is no surrounding chrome).
     * @var bool       $admin   Use admin-oriented actions (dashboard / go back).
     */
    $bare ??= false;
    $admin ??= false;

    $primary = 'inline-flex h-11 items-center rounded-md bg-brand-500 px-5 text-sm font-semibold text-white transition hover:bg-brand-600';
    $ghost = 'inline-flex h-11 items-center rounded-md border border-zinc-200 px-5 text-sm font-semibold text-ink transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-white dark:hover:bg-zinc-800';
@endphp

<section class="mx-auto flex w-full max-w-md flex-col items-center px-5 py-16 text-center sm:py-24">
    @if ($bare)
        <a href="{{ url('/') }}" class="mb-10 inline-flex" aria-label="{{ config('app.name', 'Sheffield') }} — Home">
            <img src="/logo.png" alt="{{ config('app.name', 'Sheffield') }}" class="h-10 w-auto" />
        </a>
    @endif

    <p class="font-serif text-7xl font-light leading-none tracking-tight text-brand-500 sm:text-8xl">{{ $code }}</p>

    <h1 class="mt-6 text-xl font-semibold text-ink dark:text-white sm:text-2xl">{{ $heading }}</h1>

    <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $message }}</p>

    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        @if ($admin)
            <a href="{{ route('dashboard') }}" class="{{ $primary }}">Back to dashboard</a>
            <a href="javascript:history.back()" class="{{ $ghost }}">Go back</a>
        @else
        @switch((int) $code)
            @case(404)
                <a href="{{ url('/shop') }}" class="{{ $primary }}">Browse the shop</a>
                <a href="{{ url('/') }}" class="{{ $ghost }}">Back to home</a>
                @break
            @case(403)
                <a href="{{ url('/') }}" class="{{ $primary }}">Back to home</a>
                <a href="{{ url('/contact') }}" class="{{ $ghost }}">Contact us</a>
                @break
            @case(419)
                <a href="javascript:history.back()" class="{{ $primary }}">Go back &amp; retry</a>
                <a href="{{ url('/') }}" class="{{ $ghost }}">Back to home</a>
                @break
            @case(429)
            @case(500)
                <a href="javascript:location.reload()" class="{{ $primary }}">Try again</a>
                <a href="{{ url('/') }}" class="{{ $ghost }}">Back to home</a>
                @break
            @case(503)
                <a href="javascript:location.reload()" class="{{ $primary }}">Try again</a>
                @break
            @default
                <a href="{{ url('/') }}" class="{{ $primary }}">Back to home</a>
        @endswitch
        @endif
    </div>
</section>
