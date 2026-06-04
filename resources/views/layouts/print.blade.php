<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>

<body class="min-h-screen bg-zinc-100">
    {{ $slot }}
    @fluxScripts
</body>

</html>
