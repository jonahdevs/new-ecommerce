<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>@yield('title', 'Document')</title>
    @vite('resources/css/app.css')
    <style>
        /* Base PDF styles */
        body {
            -webkit-print-color-adjust: exact;
        }
        @page {
            margin: 0;
            size: A4;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body class="antialiased text-sm text-zinc-700 tracking-tight font-sans bg-white min-h-screen">
    <div class="relative min-h-screen flex flex-col">
        @yield('content')
    </div>
</body>
</html>
