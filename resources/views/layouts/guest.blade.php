<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
</head>

<body class="bg-zinc-100 text-zinc-800 font-sans">

    <livewire:app-bar />

    <main>
        {{ $slot }}
    </main>

    @fluxScripts
    <x-footer />
</body>

</html>
