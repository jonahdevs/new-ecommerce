<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>@yield('title', 'Document')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#185FA5',
                    }
                }
            }
        }
    </script>
    <style>
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4;
            margin: 10mm 0 40mm 0;
        }

        @page :first {
            margin: 0mm 0mm 40mm 0mm;
            /* No margins on first page */
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body class="antialiased text-sm text-zinc-700 tracking-tight font-sans bg-white">
    <div class="relative flex flex-col">
        @yield('content')
    </div>
</body>

</html>
