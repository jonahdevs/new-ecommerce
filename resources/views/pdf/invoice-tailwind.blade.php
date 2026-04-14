<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Invoice {{ $order->reference }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            margin: 0;
            size: A4 portrait;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            background: white;
            padding: 0;
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="max-w-4xl mx-auto bg-white">
        {{-- HEADER --}}
        <div class="px-12 py-6">
            <div class="text-3xl font-bold text-green-600">
                Invoice
            </div>
        </div>

        {{-- GREEN DIVIDER --}}
        <div class="w-full h-1 bg-green-600"></div>

        {{-- SUPPLIER INFO & QR CODE --}}
        <div class="px-12 py-8 flex justify-between items-start">
            <div>
                <div class="text-sm font-semibold text-gray-500 uppercase mb-3">Supplier</div>
                <div class="text-xl font-bold text-gray-800 mb-2">Sheffield Steel Systems Limited</div>
                <div class="text-sm text-gray-600 leading-relaxed">
                    Off Old Mombasa Road, Opposite Hilton Garden Inn<br>
                    Nairobi<br>
                    Kenya
                </div>
                @if ($order->kra_cu_number)
                    <div class="mt-3 text-sm text-gray-600">
                        <div>KRA PIN: <span class="font-medium">P051148391Z</span></div>
                        <div>SCU ID: <span class="font-medium">KRACU04000001006</span></div>
                    </div>
                @endif
            </div>

            {{-- QR CODE --}}
            @if ($order->kra_cu_number)
                <div class="flex-shrink-0">
                    <div class="w-32 h-32 border-2 border-gray-300 flex items-center justify-center bg-white">
                        <span class="text-xs text-gray-400">[QR CODE]</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- INVOICE DETAILS GRID --}}
        <div class="px-12 py-6 bg-gray-50">
            <div class="grid grid-cols-3 gap-x-8 gap-y-4 text-sm">
                {{-- Row 1 --}}
                <div>
                    <div class="text-gray-500 mb-1">Invoice date:</div>
                    <div class="text-gray-800 font-medium">{{ $order->created_at->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="text-gray-500 mb-1">Invoice time:</div>
                    <div class="text-gray-800 font-medium">{{ $order->created_at->format('H:i') }}</div>
                </div>
                <div>
                    <div class="text-gray-500 mb-1">Date of delivery:</div>
                    <div class="text-gray-800 font-medium">
                        {{ $order->deliveryOrder?->delivered_at ? $order->deliveryOrder->delivered_at->format('d/m/Y H:i') : 'Pending' }}
                    </div>
                </div>

                {{-- Row 2 --}}
                <div>
                    <div class="text-gray-500 mb-1">Payment method:</div>
                    <div class="text-gray-800 font-medium">{{ ucfirst($order->payment?->gateway ?? 'N/A') }}</div>
                </div>
                @if ($order->kra_cu_number)
                    <div class="col-span-2">
                        <div class="text-gray-500 mb-1">CU Invoice Number</div>
                        <div class="text-gray-800 font-medium">{{ $order->kra_cu_number }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ITEMS TABLE --}}
        <div class="px-12 py-6">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">NR</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Description</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Code</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Quantity</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Price per unit
                        </th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Discount</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">VAT Rate</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Total Amount
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $index => $item)
                        @php
                            $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                            $sku = $item->product_snapshot['sku'] ?? '—';
                            $variant = $item->product_snapshot['variant'] ?? null;
                            $taxPercentage =
                                $item->unit_price_cents > 0
                                    ? ($item->unit_tax_cents / $item->unit_price_cents) * 100
                                    : 16.0;
                            $discountAmount = $item->discount_cents / 100;
                        @endphp
                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-4 text-gray-700">{{ $index + 1 }}</td>
                            <td class="px-4 py-4 text-gray-800">
                                {{ $name }}
                                @if ($variant && isset($variant['attributes']))
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        {{ collect($variant['attributes'])->map(fn($v, $k) => "$k: $v")->join(', ') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-gray-600">{{ $sku }}</td>
                            <td class="px-4 py-4 text-center text-gray-700">{{ $item->quantity }}</td>
                            <td class="px-4 py-4 text-right text-gray-700">KES
                                {{ number_format($item->unit_price_cents / 100, 2) }}</td>
                            <td class="px-4 py-4 text-right text-gray-700">
                                {{ $discountAmount > 0 ? 'KES -' . number_format($discountAmount, 2) : '-' }}</td>
                            <td class="px-4 py-4 text-right text-gray-700">{{ number_format($taxPercentage, 0) }}%
                            </td>
                            <td class="px-4 py-4 text-right font-semibold text-gray-800">KES
                                {{ number_format($item->total_cents / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- TOTALS --}}
        <div class="px-12 py-6">
            <div class="flex justify-end">
                <div class="w-96">
                    @php
                        $taxAmount = $order->tax_cents / 100;
                        $netAmount = $order->subtotal;
                        $totalExclVat = $order->total - $taxAmount;
                    @endphp

                    <div class="flex justify-between py-2 text-sm border-t border-gray-300">
                        <span class="text-gray-600">TOTAL EXCL. VAT</span>
                        <span class="font-semibold text-gray-800">KES {{ number_format($totalExclVat, 2) }}</span>
                    </div>

                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-gray-600">VAT AMOUNT</span>
                        <span class="font-semibold text-gray-800">KES {{ number_format($taxAmount, 2) }}</span>
                    </div>

                    @if ($order->discount > 0)
                        <div class="flex justify-between py-2 text-sm">
                            <span class="text-gray-600">DISCOUNT</span>
                            <span class="font-semibold text-gray-800">KES
                                -{{ number_format($order->discount, 2) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between py-3 text-base border-t-2 border-gray-800 mt-2">
                        <span class="font-bold text-gray-800">INVOICE TOTAL</span>
                        <span class="font-bold text-gray-900 text-lg">KES {{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="px-12 py-8 text-center text-sm text-gray-500">
            <div class="mb-2">Serving you is our delight. Thank you for your business.</div>
            <div class="text-xs">
                +254 713 444 000 / +254 713 777 111 &nbsp;·&nbsp; control@sheffieldafrica.com &nbsp;·&nbsp;
                sheffieldafrica.com
            </div>
        </div>
    </div>
</body>

</html>
