<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="{{ url('/') }}">
    @vite('resources/css/app.css')
    <style>
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        @page { size: A4 landscape; margin: 12mm 14mm 14mm 14mm; }
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
@endphp

<div class="mx-auto bg-white font-sans text-[12px] text-zinc-800">

    {{-- HEADER --}}
    <div class="flex items-start justify-between gap-6 pb-4">
        <div>
            <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-10 w-auto" />
            <div class="mt-2 space-y-0.5 text-[10.5px] leading-snug text-zinc-500">
                @if ($business->address)<div>{{ $business->address }}</div>@endif
                @if ($business->contact_phone)<div>Tel: {{ $business->contact_phone }}</div>@endif
                @if ($business->contact_email)<div>Email: {{ $business->contact_email }}</div>@endif
            </div>
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold uppercase tracking-widest text-zinc-900">Orders Report</div>
            <div class="mt-1.5 text-[11px] text-zinc-500">Generated {{ now()->format('d F Y, H:i') }}</div>
            <div class="mt-0.5 text-[11px] text-zinc-500">{{ $orders->count() }} order(s)</div>
        </div>
    </div>

    {{-- Double-rule separator --}}
    <div class="border-t-2 border-zinc-900"></div>
    <div class="mt-px border-t border-zinc-900"></div>

    {{-- TABLE --}}
    <div class="mt-5">
        <table class="w-full text-[11.5px]" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-left w-6">#</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-left">Order No.</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-left">Customer</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-center w-10">Items</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-right">Subtotal</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-right">Delivery</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-right">VAT</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-right">Total</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-center">Payment</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-center">Status</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-center w-24">Placed</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-zinc-500 text-center">{{ $loop->iteration }}</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top font-mono font-semibold text-zinc-900">{{ $order->order_number }}</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top">
                            <div class="font-semibold text-zinc-900">{{ $order->user?->name ?? '—' }}</div>
                            <div class="text-[10px] text-zinc-400 mt-0.5">{{ $order->user?->email }}</div>
                        </td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-center tabular-nums text-zinc-700">{{ $order->items_count }}</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-right tabular-nums text-zinc-700">{!! money($order->subtotal_cents) !!}</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-right tabular-nums text-zinc-700">{!! money($order->delivery_cents) !!}</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-right tabular-nums text-zinc-700">{!! money($order->vat_cents) !!}</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-right tabular-nums font-semibold text-zinc-900">{!! money($order->total_cents) !!}</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-center text-zinc-700">
                            {{ $order->latestPayment?->status->label() ?? 'Unpaid' }}
                        </td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-center text-zinc-700">{{ $order->status->label() }}</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-center text-zinc-600">{{ $order->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="border border-zinc-300 px-4 py-8 text-center text-zinc-400">
                            No orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($orders->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="7" class="border border-zinc-300 bg-zinc-100 px-2 py-2 text-right font-bold text-zinc-900">Grand Total</td>
                        <td class="border border-zinc-300 bg-zinc-100 px-2 py-2 text-right tabular-nums font-bold text-zinc-900">
                            {!! money($orders->sum('total_cents')) !!}
                        </td>
                        <td colspan="3" class="border border-zinc-300 bg-zinc-100"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- FOOTER --}}
    <div class="mt-6 border-t border-zinc-300 pt-3 flex items-center justify-between text-[10px] text-zinc-400">
        <span>{{ $storeName }}</span>
        <span>Exported {{ now()->format('d M Y, H:i') }}</span>
    </div>

</div>
</body>
</html>
