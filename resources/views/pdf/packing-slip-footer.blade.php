<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
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
            font-size: 9px;
            color: #9CA3AF;
        }

        .footer {
            width: 100%;
            padding: 8px 40px;
            border-top: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>

<body>
    <div class="footer">
        <span>Generated {{ now()->format('d M Y, H:i') }} · Internal document — not for customer</span>
        <span>Page <span class="pageNumber"></span> of <span class="totalPages"></span></span>
    </div>
</body>

</html>
