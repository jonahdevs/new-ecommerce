<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="{{ url('/') }}">
    @vite('resources/css/app.css')
    <style>
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

        /* Reserve bottom margin for the Browsershot footer on every page */
        @page { size: A4; margin: 10mm 0 38mm 0; }
        @page :first { margin: 0mm 0mm 38mm 0mm; }

        /* Hide the in-document footer — it is rendered by footerView() instead */
        #quote-footer { display: none !important; }
    </style>
</head>
<body class="bg-white">
    <x-quote-document :quote="$quote" />
</body>
</html>
