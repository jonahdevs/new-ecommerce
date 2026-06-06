<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="{{ url('/') }}">
    @vite('resources/css/app.css')
    <style>
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        @page { size: A4; margin: 10mm 10mm 22mm 10mm; }
        @media print { tr { page-break-inside: avoid; } }
    </style>
</head>
<body class="bg-white">

@php
    $branding  = app(\App\Settings\BrandingSettings::class);
    $business  = app(\App\Settings\BusinessSettings::class);
    $storeName = $branding->store_name ?: config('app.name');
    $logoUrl   = $branding->logo_path
                   ? \Illuminate\Support\Facades\Storage::disk('public')->url($branding->logo_path)
                   : '/logo.png';
    $address   = $order->address;
    $customer  = $order->user;
@endphp

<div class="mx-auto max-w-2xl bg-white font-sans text-[12px] text-zinc-800">

    {{-- HEADER --}}
    <div class="flex items-start justify-between gap-6 pb-4">
        <div class="flex-1">
            <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-10 w-auto" />
            <div class="mt-2 space-y-0.5 text-[10.5px] leading-snug text-zinc-500">
                @if ($business->address)<div>{{ $business->address }}</div>@endif
                @if ($business->contact_phone)<div>Tel: {{ $business->contact_phone }}</div>@endif
                @if ($business->contact_email)<div>Email: {{ $business->contact_email }}</div>@endif
            </div>
        </div>
        <div class="shrink-0 text-right">
            <div class="text-xl font-bold uppercase tracking-widest text-zinc-900">Packing List</div>
            <table class="mt-2 ml-auto text-[10.5px]" style="border-collapse: collapse;">
                <thead>
                    <tr>
                        <th class="border border-zinc-900 bg-zinc-50 px-3 py-1 font-bold text-zinc-900 text-center">DATE</th>
                        <th class="border border-zinc-900 bg-zinc-50 px-3 py-1 font-bold text-zinc-900 text-center">ORDER NO.</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border border-zinc-900 bg-white px-3 py-1 text-center">{{ now()->format('d/m/Y') }}</td>
                        <td class="border border-zinc-900 bg-white px-3 py-1 font-mono font-semibold text-center">{{ $order->order_number }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Double-rule separator --}}
    <div class="border-t-2 border-zinc-900"></div>
    <div class="mt-px border-t border-zinc-900"></div>

    {{-- SHIP TO --}}
    <div class="mt-5">
        <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-700 mb-1.5">Ship to:</div>
        <div class="inline-block border border-zinc-900 px-4 py-2.5 text-[11.5px] leading-snug min-w-[14rem]"
             style="box-shadow: 3px 3px 0 rgba(0,0,0,0.75);">
            <div class="font-bold uppercase text-zinc-900">{{ $customer?->name ?? $order->shipping_name }}</div>
            @if ($customer?->email)
                <div class="text-zinc-600">{{ $customer->email }}</div>
            @endif
            @if ($address?->phone ?? $order->shipping_phone)
                <div>Tel: {{ $address?->phone ?? $order->shipping_phone }}</div>
            @endif
            @if ($address)
                <div class="mt-1 pt-1 border-t border-zinc-200 text-zinc-500">
                    {{ collect([$address->line1, $address->line2, $address->city])->filter()->implode(', ') }}
                </div>
            @elseif ($order->shipping_line1)
                <div class="mt-1 pt-1 border-t border-zinc-200 text-zinc-500">
                    {{ collect([$order->shipping_line1, $order->shipping_line2, $order->shipping_city])->filter()->implode(', ') }}
                </div>
            @endif
            @if ($address?->delivery_instructions)
                <div class="mt-1 pt-1 border-t border-zinc-200 text-[10px] text-zinc-500 italic">
                    Note: {{ $address->delivery_instructions }}
                </div>
            @endif
        </div>
    </div>

    @if ($order->shippingMethod)
        <div class="mt-2 text-[10.5px] text-zinc-500">
            Delivery method: <span class="font-semibold text-zinc-700">{{ $order->shippingMethod->name }}</span>
        </div>
    @endif

    {{-- ITEMS TABLE --}}
    <div class="mt-5">
        <table class="w-full text-[12px]" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-left w-7">#</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-left">Description</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-center w-14">Qty</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-center w-20">Packed ✓</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $index => $item)
                    @php
                        $snapshot = $item->product_snapshot ?? [];
                        $name     = $snapshot['name'] ?? '—';
                        $sku      = $snapshot['sku'] ?? null;
                        $model    = $snapshot['model_number'] ?? null;
                    @endphp
                    <tr>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-zinc-500">{{ $index + 1 }}.</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top">
                            <div class="font-bold uppercase text-zinc-900 underline">{{ $name }}</div>
                            @if ($sku || $model)
                                <ul class="mt-1 ml-4 list-disc text-[10.5px] text-zinc-600 space-y-0.5">
                                    @if ($sku)<li>SKU: {{ $sku }}</li>@endif
                                    @if ($model)<li>Model: {{ $model }}</li>@endif
                                </ul>
                            @endif
                        </td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-center font-bold tabular-nums text-zinc-900">{{ $item->quantity }}</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-center text-zinc-300 text-lg">□</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="2" class="border border-zinc-300 bg-zinc-50 px-2 py-2 text-right font-bold text-zinc-700">Total items</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-2 text-center font-bold tabular-nums text-zinc-900">{{ $order->items->sum('quantity') }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-2"></td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- CUSTOMER NOTES --}}
    @if ($order->notes)
        <div class="mt-4 border border-amber-200 bg-amber-50 rounded px-4 py-2.5 text-[11px]">
            <div class="font-bold text-amber-800 uppercase tracking-wide text-[10px] mb-1">Customer note</div>
            <p class="text-zinc-700">{{ $order->notes }}</p>
        </div>
    @endif

    {{-- PREPARED BY --}}
    <div class="mt-8 border-t border-zinc-300 pt-4 text-[10.5px] text-zinc-500">
        Prepared by: <span class="inline-block border-b border-zinc-400 min-w-[160px] ml-1">&nbsp;</span>
    </div>

</div>
</body>
</html>
