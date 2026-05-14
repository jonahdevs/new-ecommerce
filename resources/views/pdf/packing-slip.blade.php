<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Packing Slip - {{ $order->reference }}</title>
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
            margin: 10mm;
        }

        .barcode {
            font-family: 'Libre Barcode 39', monospace;
            font-size: 48px;
        }
    </style>
</head>

<body class="antialiased text-sm text-zinc-700 tracking-tight font-sans bg-white">
    @php
        $logoPath = public_path('logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $delivery = $order->deliveryOrder;
        $shippingMethod = $delivery?->shippingMethod;
        $pickupStation = $delivery?->pickupStation;
    @endphp

    {{-- ================================================================== --}}
    {{-- HEADER                                                              --}}
    {{-- ================================================================== --}}
    <div class="flex justify-between items-start border-b-2 border-gray-300 pb-4 mb-6">
        <div>
            @if ($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Sheffield Africa" class="h-10 w-auto">
            @else
                <div class="text-xl font-bold text-brand uppercase">SHEFFIELD</div>
            @endif
        </div>

        <div class="text-right">
            <h1 class="text-2xl font-bold text-gray-900 uppercase">Packing Slip</h1>
            <div class="text-sm text-gray-500 mt-1">For warehouse use only</div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- ORDER INFO & BARCODE                                                --}}
    {{-- ================================================================== --}}
    <div class="flex justify-between items-start mb-6">
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-500 uppercase w-28">Order #:</span>
                <span class="text-lg font-bold text-gray-900">{{ $order->reference }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-500 uppercase w-28">Order Date:</span>
                <span class="text-sm text-gray-900">{{ $order->created_at->format('d M Y, H:i') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-500 uppercase w-28">Status:</span>
                <span class="text-sm font-semibold text-gray-900">{{ $order->status->label() }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-500 uppercase w-28">Total Items:</span>
                <span class="text-sm font-bold text-gray-900">{{ $order->items->sum('quantity') }}</span>
            </div>
        </div>

        {{-- QR Code for scanning --}}
        <div class="flex flex-col items-center">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($order->reference) }}"
                alt="QR Code" class="w-24 h-24">
            <div class="text-xs text-gray-500 mt-1">Scan to view order</div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- SHIPPING INFO                                                       --}}
    {{-- ================================================================== --}}
    <div class="grid grid-cols-2 gap-6 mb-6">
        {{-- Ship To --}}
        <div class="border border-gray-300 rounded">
            <div class="px-4 py-2 bg-gray-100 border-b border-gray-300">
                <div class="text-xs font-bold text-gray-700 uppercase">Ship To</div>
            </div>
            <div class="p-4">
                <div class="font-bold text-gray-900">{{ $order->customerName() }}</div>
                @if ($order->customerPhone())
                    <div class="text-sm text-gray-600 mt-1">{{ $order->customerPhone() }}</div>
                @endif
                @if ($order->shipping_address)
                    <div class="text-sm text-gray-600 mt-2 leading-relaxed">
                        {{ $order->shipping_address['address'] ?? '' }}<br>
                        {{ implode(
                            ', ',
                            array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null]),
                        ) }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Shipping Method --}}
        <div class="border border-gray-300 rounded">
            <div class="px-4 py-2 bg-gray-100 border-b border-gray-300">
                <div class="text-xs font-bold text-gray-700 uppercase">Shipping Method</div>
            </div>
            <div class="p-4">
                @if ($shippingMethod)
                    <div class="font-bold text-gray-900">{{ $shippingMethod->name }}</div>
                @elseif ($order->wasConvertedFromQuote())
                    <div class="font-bold text-gray-900">Quote Delivery</div>
                    <div class="text-sm text-gray-500 mt-1">Arranged separately</div>
                @else
                    <div class="text-gray-500">Not specified</div>
                @endif

                @if ($pickupStation)
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Pickup Station</div>
                        <div class="text-sm font-semibold text-gray-900 mt-1">{{ $pickupStation->name }}</div>
                        @if ($pickupStation->address)
                            <div class="text-xs text-gray-600 mt-1">{{ $pickupStation->address }}</div>
                        @endif
                    </div>
                @endif

                @if ($order->tracking_number)
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Tracking #</div>
                        <div class="text-sm font-mono font-semibold text-gray-900 mt-1">{{ $order->tracking_number }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- ITEMS TABLE                                                         --}}
    {{-- ================================================================== --}}
    <div class="mb-6">
        <div class="text-xs font-bold text-gray-700 uppercase mb-2">Items to Pack</div>
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th
                        class="border border-gray-300 py-2 px-3 text-xs font-bold text-gray-700 uppercase text-center w-12">
                        ✓</th>
                    <th class="border border-gray-300 py-2 px-3 text-xs font-bold text-gray-700 uppercase text-left">SKU
                    </th>
                    <th class="border border-gray-300 py-2 px-3 text-xs font-bold text-gray-700 uppercase text-left">
                        Product</th>
                    <th
                        class="border border-gray-300 py-2 px-3 text-xs font-bold text-gray-700 uppercase text-center w-20">
                        Qty</th>
                    <th
                        class="border border-gray-300 py-2 px-3 text-xs font-bold text-gray-700 uppercase text-center w-24">
                        Weight</th>
                    <th
                        class="border border-gray-300 py-2 px-3 text-xs font-bold text-gray-700 uppercase text-center w-20">
                        Picked</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    @php
                        $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku = $item->product_snapshot['sku'] ?? ($item->product?->sku ?? '—');
                        $weight = $item->product_snapshot['weight_kg'] ?? ($item->product?->weight_kg ?? null);
                        $variantAttrs = $item->product_snapshot['variant']['attributes'] ?? [];
                        $isBundle = ($item->product_snapshot['type'] ?? null) === 'bundle';
                        $bundleContents = $item->product_snapshot['bundle_contents'] ?? [];
                    @endphp
                    <tr>
                        <td class="border border-gray-300 py-3 px-3 text-center">
                            <div class="w-5 h-5 border-2 border-gray-400 rounded mx-auto"></div>
                        </td>
                        <td class="border border-gray-300 py-3 px-3">
                            <div class="text-xs font-mono text-gray-600">{{ $sku }}</div>
                        </td>
                        <td class="border border-gray-300 py-3 px-3">
                            <div class="font-semibold text-gray-900">{{ $name }}</div>
                            @if (!empty($variantAttrs))
                                <div class="text-xs text-gray-500 mt-0.5">
                                    {{ collect($variantAttrs)->map(fn($v, $k) => "$k: $v")->join(' · ') }}
                                </div>
                            @endif
                            @if ($isBundle && !empty($bundleContents))
                                <div class="mt-2 pl-3 border-l-2 border-blue-300">
                                    <div class="text-xs font-semibold text-blue-600 mb-1">Bundle Contains:</div>
                                    @foreach ($bundleContents as $child)
                                        <div class="text-xs text-gray-600">
                                            • {{ $child['name'] ?? 'Item' }}
                                            <span class="text-gray-400">({{ $child['sku'] ?? 'N/A' }})</span>
                                            × {{ $child['quantity'] ?? 1 }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="border border-gray-300 py-3 px-3 text-center">
                            <div class="text-lg font-bold text-gray-900">{{ $item->quantity }}</div>
                        </td>
                        <td class="border border-gray-300 py-3 px-3 text-center">
                            @if ($weight)
                                <div class="text-sm text-gray-600">{{ number_format($weight * $item->quantity, 2) }} kg
                                </div>
                            @else
                                <div class="text-xs text-gray-400">—</div>
                            @endif
                        </td>
                        <td class="border border-gray-300 py-3 px-3 text-center">
                            <div class="w-5 h-5 border-2 border-gray-400 rounded mx-auto"></div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ================================================================== --}}
    {{-- TOTALS SUMMARY                                                      --}}
    {{-- ================================================================== --}}
    <div class="flex justify-between items-start mb-6">
        <div class="text-sm text-gray-600">
            <strong>Total SKUs:</strong> {{ $order->items->count() }} |
            <strong>Total Units:</strong> {{ $order->items->sum('quantity') }}
            @php
                $totalWeight = $order->items->sum(function ($item) {
                    $weight = $item->product_snapshot['weight_kg'] ?? ($item->product?->weight_kg ?? 0);
                    return $weight * $item->quantity;
                });
            @endphp
            @if ($totalWeight > 0)
                | <strong>Est. Weight:</strong> {{ number_format($totalWeight, 2) }} kg
            @endif
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- CUSTOMER NOTES                                                      --}}
    {{-- ================================================================== --}}
    @if ($order->customer_notes)
        <div class="border border-amber-300 bg-amber-50 rounded p-4 mb-6">
            <div class="text-xs font-bold text-amber-700 uppercase mb-1">⚠️ Customer Notes</div>
            <div class="text-sm text-amber-800">{{ $order->customer_notes }}</div>
        </div>
    @endif

    {{-- ================================================================== --}}
    {{-- PACKING VERIFICATION                                                --}}
    {{-- ================================================================== --}}
    <div class="border-t-2 border-gray-300 pt-4 mt-6">
        <div class="grid grid-cols-2 gap-6">
            <div>
                <div class="text-xs font-bold text-gray-500 uppercase mb-2">Packed By</div>
                <div class="border-b border-gray-400 h-8"></div>
                <div class="text-xs text-gray-400 mt-1">Name & Signature</div>
            </div>
            <div>
                <div class="text-xs font-bold text-gray-500 uppercase mb-2">Date & Time</div>
                <div class="border-b border-gray-400 h-8"></div>
                <div class="text-xs text-gray-400 mt-1">DD/MM/YYYY HH:MM</div>
            </div>
        </div>

        <div class="mt-4">
            <div class="text-xs font-bold text-gray-500 uppercase mb-2">Quality Check</div>
            <div class="flex gap-6 text-sm">
                <label class="flex items-center gap-2">
                    <div class="w-4 h-4 border-2 border-gray-400 rounded"></div>
                    <span class="text-gray-600">All items present</span>
                </label>
                <label class="flex items-center gap-2">
                    <div class="w-4 h-4 border-2 border-gray-400 rounded"></div>
                    <span class="text-gray-600">Items undamaged</span>
                </label>
                <label class="flex items-center gap-2">
                    <div class="w-4 h-4 border-2 border-gray-400 rounded"></div>
                    <span class="text-gray-600">Properly packed</span>
                </label>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- FOOTER                                                              --}}
    {{-- ================================================================== --}}
    <div class="mt-8 pt-4 border-t border-gray-200 text-center text-xs text-gray-400">
        Generated on {{ now()->format('d M Y, H:i') }} | Internal document - Not for customer
    </div>
</body>

</html>
