<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-50 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-24 text-center">
        <div class="mx-auto w-full max-w-md">
            <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-zinc-100">
                <svg class="size-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
            <h1 class="font-serif text-3xl text-zinc-900">You've been unsubscribed.</h1>
            <p class="mt-4 text-base leading-relaxed text-zinc-500">
                We've removed your address from The Sheffield Quarterly.
                You won't receive any further emails from us.
            </p>
            <a href="{{ route('home') }}"
                class="mt-8 inline-flex items-center gap-2 rounded-lg border border-zinc-200 px-6 py-3 text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50">
                Back to the shop
            </a>
        </div>
    </div>
</body>
</html>
