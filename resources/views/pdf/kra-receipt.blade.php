<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="{{ url('/') }}">
    @vite('resources/css/app.css')
    <style>
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        @page { size: A4; margin: 10mm 0 38mm 0; }
        @media print { tr { page-break-inside: avoid; } }
    </style>
</head>
<body class="bg-white">

@php
    use BaconQrCode\Renderer\ImageRenderer;
    use BaconQrCode\Renderer\Image\SvgImageBackEnd;
    use BaconQrCode\Renderer\RendererStyle\RendererStyle;
    use BaconQrCode\Writer;

    $branding    = app(\App\Settings\BrandingSettings::class);
    $business    = app(\App\Settings\BusinessSettings::class);
    $storeName   = $branding->store_name ?: config('app.name');
    $logoUrl     = $branding->logo_path
                     ? \Illuminate\Support\Facades\Storage::disk('public')->url($branding->logo_path)
                     : '/logo.png';
    $pin         = $businessPin ?: $business->tax_pin;
    $address     = $order->address;
    $customer    = $order->user;
    $vatRate     = $order->items->first()?->tax_rate ?? 16;

    $kraVerifyUrl = $order->kra_cu_number
        ? 'https://itax.kra.go.ke/KRA-Portal/invoiceChk.htm?actionCode=loadPage&invoiceNo=' . $order->kra_cu_number
        : null;

    $qrSvg = null;
    if ($kraVerifyUrl) {
        $renderer = new ImageRenderer(new RendererStyle(120), new SvgImageBackEnd());
        $qrSvg    = (new Writer($renderer))->writeString($kraVerifyUrl);
    }
@endphp

<div class="mx-auto max-w-2xl bg-white font-sans text-[12px] text-zinc-800">

    {{-- ======================================================= --}}
    {{-- HEADER --}}
    {{-- ======================================================= --}}
    <div class="px-8 pt-7 pb-4">
        <div class="flex items-start justify-between gap-6">

            <div class="flex-1">
                <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-10 w-auto" />
                <div class="mt-2 space-y-0.5 text-[10.5px] leading-snug text-zinc-500">
                    @if ($business->address)
                        <div>{{ $business->address }}</div>
                    @endif
                    @if ($business->contact_phone)
                        <div>Tel: {{ $business->contact_phone }}</div>
                    @endif
                    @if ($business->contact_email)
                        <div>Email: {{ $business->contact_email }}</div>
                    @endif
                    @if ($pin)
                        <div>PIN: {{ $pin }}</div>
                    @endif
                </div>
            </div>

            <div class="shrink-0 text-right">
                <div class="text-lg font-bold uppercase tracking-widest text-zinc-900">Tax Receipt</div>
                <table class="mt-2 ml-auto text-[10.5px]" style="border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="border border-zinc-900 bg-zinc-50 px-3 py-1 font-bold text-zinc-900 text-center">DATE</th>
                            <th class="border border-zinc-900 bg-zinc-50 px-3 py-1 font-bold text-zinc-900 text-center">ORDER NO.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-zinc-900 bg-white px-3 py-1 text-center">
                                {{ $order->created_at->format('d/m/Y') }}
                            </td>
                            <td class="border border-zinc-900 bg-white px-3 py-1 font-mono font-semibold text-center">
                                {{ $order->order_number }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mx-8 border-t-2 border-zinc-900"></div>
    <div class="mx-8 mt-px border-t border-zinc-900"></div>

    {{-- ======================================================= --}}
    {{-- BILL TO --}}
    {{-- ======================================================= --}}
    <div class="px-8 mt-5">
        <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-700 mb-1.5">Billed to:</div>
        <div class="inline-block border border-zinc-900 px-4 py-2.5 text-[11px] leading-snug min-w-[14rem]"
             style="box-shadow: 3px 3px 0 rgba(0,0,0,0.75);">
            @if ($customer?->name || $order->shipping_name)
                <div class="font-bold uppercase text-zinc-900">{{ $customer?->name ?? $order->shipping_name }}</div>
            @endif
            @if ($customer?->email)
                <div class="text-zinc-600">{{ $customer->email }}</div>
            @endif
            @if ($address?->phone || $order->shipping_phone)
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
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- ITEMS TABLE --}}
    {{-- ======================================================= --}}
    <div class="px-8 mt-6">
        <table class="w-full text-[11.5px]" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-left w-7">#</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-left">Description</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-center w-10">Qty</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-right w-24">Unit price</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-right w-24">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $index => $item)
                    @php
                        $snapshot = $item->product_snapshot ?? [];
                        $itemName = $snapshot['name'] ?? $item->product_snapshot['name'] ?? '—';
                        $itemSku  = $snapshot['sku'] ?? null;
                    @endphp
                    <tr>
                        <td class="border border-zinc-300 px-2 py-1.5 align-top text-zinc-500">{{ $index + 1 }}.</td>
                        <td class="border border-zinc-300 px-2 py-1.5 align-top">
                            <div class="font-bold uppercase text-zinc-900">{{ $itemName }}</div>
                            @if ($itemSku)
                                <div class="text-[10px] text-zinc-500 mt-0.5">SKU: {{ $itemSku }}</div>
                            @endif
                        </td>
                        <td class="border border-zinc-300 px-2 py-1.5 align-top text-center tabular-nums">{{ $item->quantity }}</td>
                        <td class="border border-zinc-300 px-2 py-1.5 align-top text-right tabular-nums text-zinc-600">{!! money($item->unit_price_cents) !!}</td>
                        <td class="border border-zinc-300 px-2 py-1.5 align-top text-right tabular-nums font-semibold text-zinc-900">{!! money($item->line_total_cents) !!}</td>
                    </tr>
                @endforeach

                {{-- Subtotal --}}
                <tr>
                    <td colspan="4" class="border border-zinc-300 px-2 py-1.5 text-right text-zinc-500">Subtotal</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right tabular-nums font-semibold">{!! money($order->subtotal_cents) !!}</td>
                </tr>

                {{-- Delivery --}}
                @if ($order->delivery_cents > 0)
                    <tr>
                        <td colspan="4" class="border border-zinc-300 px-2 py-1.5 text-right text-zinc-500">Delivery</td>
                        <td class="border border-zinc-300 px-2 py-1.5 text-right tabular-nums">{!! money($order->delivery_cents) !!}</td>
                    </tr>
                @endif

                {{-- VAT --}}
                @if ($order->vat_cents > 0)
                    <tr>
                        <td colspan="4" class="border border-zinc-300 px-2 py-1.5 text-right text-zinc-500">VAT ({{ $vatRate }}%)</td>
                        <td class="border border-zinc-300 px-2 py-1.5 text-right tabular-nums">{!! money($order->vat_cents) !!}</td>
                    </tr>
                @endif

                {{-- Grand total --}}
                <tr>
                    <td colspan="4" class="border border-zinc-300 bg-zinc-100 px-2 py-2.5 text-right font-bold text-zinc-900 text-[12.5px]">
                        Total (KES)
                    </td>
                    <td class="border border-zinc-300 bg-zinc-100 px-2 py-2.5 text-right font-bold text-zinc-900 text-[12.5px] tabular-nums">
                        {!! money($order->total_cents) !!}
                    </td>
                </tr>

                <tr>
                    <td colspan="5" class="border border-zinc-300 px-2 py-1.5 text-center text-[10px] text-zinc-400 italic">
                        * All prices are inclusive of VAT at {{ $vatRate }}%. This is a KRA-validated tax receipt.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ======================================================= --}}
    {{-- PAYMENT METHOD --}}
    {{-- ======================================================= --}}
    @if ($order->payment_method)
        <div class="px-8 mt-4 text-[11px] text-zinc-600">
            <span class="font-semibold text-zinc-800">Payment method:</span>
            {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
        </div>
    @endif

    {{-- ======================================================= --}}
    {{-- SIGN-OFF + QR CODE --}}
    {{-- ======================================================= --}}
    <div class="px-8 mt-6 pb-8">

        {{-- Sign-off (quotation style) --}}
        <div class="text-[11px] text-zinc-600">
            <p>This receipt has been validated by the Kenya Revenue Authority (KRA).<br>Please retain it for your records.</p>
            <p class="mt-3">Best regards,<br><strong>{{ $storeName }}</strong></p>
        </div>

        {{-- QR code + CU number — centered --}}
        @if ($qrSvg)
            <div class="mt-6 flex flex-col items-center text-center">
                <div class="inline-block">
                    {!! $qrSvg !!}
                </div>
                <div class="mt-1.5 text-[9px] font-semibold uppercase tracking-wider text-zinc-500">Verify on iTax</div>
                @if ($order->kra_cu_number)
                    <div class="mt-0.5 font-mono text-[9.5px] font-bold text-zinc-700">{{ $order->kra_cu_number }}</div>
                @endif
            </div>
        @endif

    </div>

</div>
</body>
</html>
