<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription confirmed — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-50 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-24 text-center">
        <div class="mx-auto w-full max-w-md">
            <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-emerald-100">
                <svg class="size-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="font-serif text-3xl text-zinc-900">You're confirmed.</h1>
            <p class="mt-4 text-base leading-relaxed text-zinc-500">
                Welcome to The Sheffield Quarterly. You'll hear from us four times a year —
                catalog drops, project stories, and trade-only offers. No noise in between.
            </p>
            <a href="{{ route('home') }}"
                class="mt-8 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-600">
                Back to the shop
            </a>
        </div>
    </div>
</body>
</html>
