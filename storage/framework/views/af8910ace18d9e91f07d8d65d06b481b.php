<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $__env->yieldContent('title', 'Document'); ?></title>
    <style>
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #374151;
            background-color: white;
        }

        @page {
            size: A4;
            margin: 10mm 0 40mm 0;
        }

        @page :first {
            margin: 0mm 0mm 40mm 0mm;
        }

        .page-break {
            page-break-after: always;
        }

        /* Utility classes for layout */
        .flex {
            display: flex;
        }

        .grid {
            display: grid;
        }

        .grid-cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .gap-4 {
            gap: 16px;
        }

        .gap-6 {
            gap: 24px;
        }

        .justify-between {
            justify-content: space-between;
        }

        .items-start {
            align-items: flex-start;
        }

        .items-center {
            align-items: center;
        }

        .flex-1 {
            flex: 1;
        }

        .flex-shrink-0 {
            flex-shrink: 0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .font-bold {
            font-weight: bold;
        }

        .font-semibold {
            font-weight: 600;
        }

        .italic {
            font-style: italic;
        }

        /* Spacing utilities */
        .mb-1 {
            margin-bottom: 4px;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .mb-3 {
            margin-bottom: 12px;
        }

        .mt-1 {
            margin-top: 4px;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mt-3 {
            margin-top: 12px;
        }

        .pt-3 {
            padding-top: 12px;
        }

        .pb-3 {
            padding-bottom: 12px;
        }

        /* Border utilities */
        .border {
            border: 1px solid #d1d5db;
        }

        .border-t {
            border-top: 1px solid #e5e7eb;
        }

        .border-b {
            border-bottom: 1px solid #e5e7eb;
        }

        /* Background utilities */
        .bg-gray-50 {
            background-color: #f9fafb;
        }

        .bg-white {
            background-color: white;
        }

        /* Color utilities */
        .text-gray-900 {
            color: #111827;
        }

        .text-gray-700 {
            color: #374151;
        }

        .text-gray-600 {
            color: #4b5563;
        }

        .text-gray-500 {
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div style="position: relative; display: flex; flex-direction: column;">
        <?php echo $__env->yieldContent('content'); ?>
    </div>
</body>

</html>
<?php /**PATH C:\Users\jonah\Herd\sheffield_ecommerce\resources\views\pdf\dompdf\layouts\main.blade.php ENDPATH**/ ?>