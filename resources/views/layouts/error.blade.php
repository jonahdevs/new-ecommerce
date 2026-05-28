<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white font-sans min-h-screen flex flex-col">
    <div class="border-b border-zinc-200 py-4 px-8">
        <a href="/" class="font-serif font-extrabold text-xl tracking-tight text-zinc-950">
            {{ config('app.name') }}
        </a>
    </div>

    <main class="flex-1">
        {{ $slot }}
    </main>
</body>

</html>
