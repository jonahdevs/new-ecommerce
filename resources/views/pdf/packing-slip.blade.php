@extends('pdf.browsershot.layouts.main')

@section('title', 'Packing Slip ' . $order->reference)

@section('content')
    @php
        $general = app(\App\Settings\GeneralSettings::class);

        $logoPath = public_path('logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $companyAddressLines = array_filter([
            $general->store_address,
            $general->store_address_line_2,
            trim(implode(', ', array_filter([
                $general->store_city,
                $general->store_state,
                $general->store_postal_code,
            ]))),
            $general->store_country,
        ]);

        // Prefer live delivery order data (post-SHIPPED); fall back to shipping_snapshot (PROCESSING)
        $delivery      = $order->deliveryOrder ?? null;
        $shippingMethod = $delivery?->shippingMethod;
        $pickupStation  = $delivery?->pickupStation;
        $snapshot       = $order->shipping_snapshot ?? [];

    @endphp

    {{-- ================================================================== --}}
    {{-- HEADER — Company info (left) + PACKING SLIP + Date/Number (right)  --}}
    {{-- ================================================================== --}}
    <div class="px-10 pt-8 pb-3">
        <div class="flex justify-between items-start gap-6">

            <div class="flex-1">
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="{{ $general->store_name }}" class="h-12 w-auto mb-2">
                @endif

                <div class="text-[10px] leading-snug text-gray-800">
                    @if ($general->store_tagline)
                        <div>{{ $general->store_tagline }}</div>
                    @endif
                    @foreach ($companyAddressLines as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                    @if ($general->store_phone)
                        <div>Tel: {{ $general->store_phone }}</div>
                    @endif
                    @if ($general->store_email)
                        <div>Email: <span class="text-brand">{{ $general->store_email }}</span></div>
                    @endif
                </div>
            </div>

            <div class="text-right shrink-0">
                <div class="text-xl font-bold text-gray-900 tracking-wide mb-2">PACKING SLIP</div>
                <table class="text-xs ml-auto" style="border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="border border-gray-900 px-3 py-1 font-bold text-gray-900 bg-gray-50"
                                style="text-align: center;">ORDER #</th>
                            <th class="border border-gray-900 px-3 py-1 font-bold text-gray-900 bg-gray-50"
                                style="text-align: center;">DATE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-900 px-3 py-1 bg-white font-semibold"
                                style="text-align: center;">
                                {{ $order->reference }}
                            </td>
                            <td class="border border-gray-900 px-3 py-1 bg-white" style="text-align: center;">
                                {{ $order->created_at->format('d/m/Y') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Thick separator --}}
    <div class="mx-10 border-t-2 border-gray-900"></div>
    <div class="mx-10 border-t border-gray-900 mt-0.5"></div>

    {{-- ================================================================== --}}
    {{-- SHIP TO + SHIPPING METHOD                                           --}}
    {{-- ================================================================== --}}
    <div class="px-10 mt-5 flex gap-6 items-stretch">

        {{-- Ship To --}}
        <div class="flex flex-col">
            <div class="text-xs font-bold text-gray-900 mb-1.5">SHIP TO:</div>
            <div class="flex-1 border border-gray-900 px-3 py-2 text-[11px] leading-snug min-w-[14rem] max-w-xs"
                style="box-shadow: 3px 3px 0 rgba(0,0,0,0.85);">
                <div class="font-bold uppercase text-gray-900">{{ $order->customerName() }}</div>
                @if ($order->customerPhone())
                    <div>TEL: {{ $order->customerPhone() }}</div>
                @endif
                @if ($order->shipping_address)
                    <div class="mt-1">
                        {{ $order->shipping_address['address'] ?? '' }}<br>
                        {{ implode(', ', array_filter([
                            $order->shipping_address['area'] ?? null,
                            $order->shipping_address['county'] ?? null,
                        ])) }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Shipping Method --}}
        <div class="flex flex-col">
            <div class="text-xs font-bold text-gray-900 mb-1.5">SHIPPING METHOD:</div>
            <div class="flex-1 border border-gray-900 px-3 py-2 text-[11px] leading-snug min-w-[14rem] max-w-xs"
                style="box-shadow: 3px 3px 0 rgba(0,0,0,0.85);">
                @if ($shippingMethod)
                    <div class="font-bold uppercase text-gray-900">{{ $shippingMethod->name }}</div>
                @elseif ($snapshot['method_name'] ?? null)
                    <div class="font-bold uppercase text-gray-900">{{ $snapshot['method_name'] }}</div>
                    @if ($snapshot['delivery_window'] ?? null)
                        <div class="text-gray-600 mt-0.5">Est. {{ $snapshot['delivery_window'] }}</div>
                    @endif
                @elseif ($order->wasConvertedFromQuote())
                    <div class="font-bold uppercase text-gray-900">Quote Delivery</div>
                    <div class="text-gray-600 mt-0.5">Arranged separately</div>
                @else
                    <div class="text-gray-500 italic">Not specified</div>
                @endif

                @if ($pickupStation)
                    <div class="mt-2 pt-2 border-t border-gray-300">
                        <div class="text-[10px] font-bold text-gray-600 uppercase">Pickup Station</div>
                        <div class="font-semibold text-gray-900 mt-0.5">{{ $pickupStation->name }}</div>
                        @if ($pickupStation->address)
                            <div class="text-gray-600 mt-0.5">{{ $pickupStation->address }}</div>
                        @endif
                    </div>
                @endif

                @if ($order->tracking_number)
                    <div class="mt-2 pt-2 border-t border-gray-300">
                        <div class="text-[10px] font-bold text-gray-600 uppercase">Tracking #</div>
                        <div class="font-mono font-semibold text-gray-900 mt-0.5">{{ $order->tracking_number }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- ITEMS TABLE                                                         --}}
    {{-- ================================================================== --}}
    <div class="px-10 mt-8">
        <table class="w-full border-collapse text-xs">
            <thead>
                <tr>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 w-8 text-center">#</th>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 text-left">DETAILS</th>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 w-12 text-center">QTY</th>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 w-12 text-center">✓</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $index => $item)
                    @php
                        $name         = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku          = $item->product_snapshot['sku']  ?? ($item->product?->sku  ?? '—');
                        $brand        = $item->product_snapshot['brand'] ?? null;
                        $modelNumber  = $item->product_snapshot['model_number'] ?? ($item->product?->model_number ?? null);
                        $dimensions   = $item->product_snapshot['dimensions'] ?? ($item->product?->dimensions ?? null);
                        $weight       = $item->product_snapshot['weight_kg'] ?? ($item->product?->weight_kg ?? null);
                        $variantAttrs = $item->product_snapshot['variant']['attributes'] ?? [];
                        $isBundle     = ($item->product_snapshot['type'] ?? null) === 'bundle';
                        $bundleContents = $item->product_snapshot['bundle_contents'] ?? [];
                    @endphp
                    <tr>
                        <td class="border border-gray-400 px-2 py-2 align-top text-center text-gray-500">
                            {{ $index + 1 }}.
                        </td>
                        <td class="border border-gray-400 px-2 py-2 align-top">
                            <div class="font-bold text-gray-900 underline">{{ strtoupper($name) }}</div>
                            <ul class="mt-1 ml-4 list-disc text-[11px] text-gray-700 space-y-0.5">
                                <li>SKU: {{ $sku }}</li>
                                @if ($brand)
                                    <li>Brand: {{ $brand }}</li>
                                @endif
                                @if ($modelNumber)
                                    <li>Model No: {{ $modelNumber }}</li>
                                @endif
                                @if ($dimensions)
                                    <li>Dimensions: {{ is_array($dimensions) ? implode(' × ', array_filter($dimensions)) : $dimensions }}</li>
                                @endif
                                @if ($weight)
                                    <li>Weight: {{ number_format($weight, 2) }} kg / unit</li>
                                @endif
                                @if (!empty($variantAttrs))
                                    @foreach ($variantAttrs as $attr => $value)
                                        <li>{{ $attr }}: {{ $value }}</li>
                                    @endforeach
                                @endif
                                @if ($isBundle && !empty($bundleContents))
                                    @foreach ($bundleContents as $child)
                                        <li>
                                            {{ $child['name'] ?? 'Item' }}
                                            <span class="text-gray-400">({{ $child['sku'] ?? 'N/A' }})</span>
                                            × {{ $child['quantity'] ?? 1 }}
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </td>
                        <td class="border border-gray-400 px-2 py-2 align-top text-center font-bold text-base text-gray-900">
                            {{ $item->quantity }}
                        </td>
                        <td class="border border-gray-400 px-2 py-2 align-top text-center">
                            <div class="w-4 h-4 border-2 border-gray-500 mx-auto"></div>
                        </td>
                    </tr>
                @endforeach

                {{-- Summary row --}}
                <tr>
                    <td colspan="2" class="border border-gray-400 px-2 py-2 text-right text-gray-700 font-bold">
                        Totals
                    </td>
                    <td class="border border-gray-400 px-2 py-2 text-center font-bold text-gray-900">
                        {{ $order->items->sum('quantity') }}
                    </td>
                    <td class="border border-gray-400 px-2 py-2"></td>
                </tr>
            </tbody>
        </table>

        <div class="mt-1 text-[10px] text-gray-500">
            {{ $order->items->count() }} {{ Str::plural('line', $order->items->count()) }} ·
            {{ $order->items->sum('quantity') }} {{ Str::plural('unit', $order->items->sum('quantity')) }}
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- CUSTOMER NOTES                                                      --}}
    {{-- ================================================================== --}}
    @if ($order->customer_notes)
        <div class="px-10 mt-5">
            <div class="text-xs font-bold text-gray-700 uppercase mb-1">Customer Notes</div>
            <div class="border border-amber-400 bg-amber-50 px-3 py-2 text-[11px] text-amber-800 leading-snug">
                "{{ $order->customer_notes }}"
            </div>
        </div>
    @endif

    {{-- ================================================================== --}}
    {{-- RECEIPT VERIFICATION                                               --}}
    {{-- ================================================================== --}}
    <div class="px-10 mt-6">
        <div class="border-t-2 border-gray-900 mb-5"></div>
        <div class="flex flex-col gap-5 text-[11px] max-w-xs">
            <div>
                <div class="font-bold text-gray-700 uppercase mb-2">Received By</div>
                <div class="border-b border-gray-500 h-7"></div>
            </div>
            <div>
                <div class="font-bold text-gray-700 uppercase mb-2">Sign</div>
                <div class="border-b border-gray-500 h-7"></div>
            </div>
            <div>
                <div class="font-bold text-gray-700 uppercase mb-2">Date</div>
                <div class="border-b border-gray-500 h-7"></div>
                <div class="text-[10px] text-gray-400 mt-1">DD/MM/YYYY</div>
            </div>
        </div>
    </div>
@endsection
