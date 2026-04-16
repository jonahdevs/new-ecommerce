@extends('pdf.browsershot.layouts.main')

@section('title', 'Tax Invoice ' . $order->reference)

@section('content')
    @php
        $logoPath = public_path('logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
    @endphp

    {{-- ================================================================== --}}
    {{-- HEADER                                                              --}}
    {{-- ================================================================== --}}
    <div class="px-10 py-6 flex justify-between items-start border-b border-gray-200">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 uppercase">Tax Invoice</h1>
            <div class="flex items-center gap-2 mt-2 text-sm">
                <span class="text-gray-500">Invoice No:</span>
                <span class="text-gray-900 font-semibold">#{{ $order->reference }}</span>
            </div>
            <div class="flex items-center gap-2 mt-1 text-sm">
                <span class="text-gray-500">Date:</span>
                <span class="text-gray-900 font-semibold">{{ $order->created_at->format('d M, Y') }}</span>
            </div>
        </div>

        <div class="text-right">
            @if ($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Sheffield Africa" class="h-12 w-auto ml-auto">
            @else
                <div class="text-xl font-bold text-brand uppercase">SHEFFIELD</div>
            @endif
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- CUSTOMER & PAYMENT INFO                                            --}}
    {{-- ================================================================== --}}
    <div class="px-10 py-6 flex justify-between gap-6">
        {{-- Left: Customer & Payment Boxes --}}
        <div class="grid grid-cols-2 gap-4 flex-1">
            {{-- Customer --}}
            <div class="border border-gray-300">
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-300">
                    <div class="text-xs font-bold text-gray-700 uppercase">Customer</div>
                </div>
                <div class="p-4 space-y-2">
                    <div class="font-semibold text-sm text-gray-900">{{ $order->customerName() }}</div>
                    @if ($order->customerEmail())
                        <div class="text-xs text-gray-600">{{ $order->customerEmail() }}</div>
                    @endif
                    @if ($order->customerPhone())
                        <div class="text-xs text-gray-600">{{ $order->customerPhone() }}</div>
                    @endif

                    @if ($order->shipping_address)
                        <div class="pt-3 mt-3 border-t border-gray-200">
                            <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Delivery Address</div>
                            <div class="text-xs text-gray-600 leading-relaxed">
                                {{ $order->shipping_address['address'] ?? '' }}<br>
                                {{ implode(
                                    ', ',
                                    array_filter([$order->shipping_address['area'] ?? null, $order->shipping_address['county'] ?? null]),
                                ) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Payment --}}
            <div class="border border-gray-300">
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-300">
                    <div class="text-xs font-bold text-gray-700 uppercase">Payment Info</div>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Payment Method</div>
                        <div class="text-sm font-semibold text-gray-900 mt-0.5">
                            {{ ucfirst($order->payment?->gateway ?? 'Online Payment') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Status</div>
                        <div class="text-sm font-semibold text-gray-900 mt-0.5">
                            {{ $order->payment_status->label() }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Order Reference</div>
                        <div class="text-sm font-semibold text-gray-900 mt-0.5">#{{ $order->reference }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase">Currency</div>
                        <div class="text-sm font-semibold text-gray-900 mt-0.5">KES</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: QR Code --}}
        @if ($order->kra_cu_number)
            <div class="flex flex-col items-center justify-center shrink-0">
                <div class="w-32 h-32 bg-white p-2">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($order->kra_cu_number) }}"
                        alt="QR Code" class="w-full h-full">
                </div>
                <div class="text-xs text-gray-500 text-center font-semibold uppercase mt-2">Scan to Verify</div>
                <div class="text-xs font-mono font-semibold text-gray-900 text-center mt-2">
                    {{ $order->kra_cu_number }}
                </div>
                <div class="text-xs text-gray-600 text-center mt-1">
                    {{ $order->kra_validated_at?->format('d M Y, H:i') ?? $order->created_at->format('d M Y, H:i') }}
                </div>
            </div>
        @endif
    </div>

    {{-- ================================================================== --}}
    {{-- ITEMS TABLE                                                         --}}
    {{-- ================================================================== --}}
    <div class="px-10 py-6">
        <table class="w-full border-collapse">
            <thead class="bg-slate-50">
                <tr class="border-b-2 border-gray-300">
                    <th class="py-3 ps-2 text-xs font-bold text-gray-700 uppercase text-left">#</th>
                    <th class="py-3 text-xs font-bold text-gray-700 uppercase text-left">Description</th>
                    <th class="py-3 text-xs font-bold text-gray-700 uppercase text-center">Qty</th>
                    <th class="py-3 text-xs font-bold text-gray-700 uppercase text-right">Unit Price</th>
                    <th class="py-3 text-xs font-bold text-gray-700 uppercase text-right">Discount</th>
                    <th class="py-3 pe-2 text-xs font-bold text-gray-700 uppercase text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $index => $item)
                    @php
                        $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku = $item->product_snapshot['sku'] ?? '—';
                        $discountAmount = $item->discount_cents / 100;
                    @endphp
                    <tr class="border-b border-gray-200">
                        <td class="py-3 text-xs text-gray-500">{{ $index + 1 }}</td>
                        <td class="py-3">
                            <div class="text-sm font-semibold text-gray-900">{{ $name }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $sku }}</div>
                        </td>
                        <td class="py-3 text-sm text-gray-900 text-center">{{ $item->quantity }}</td>
                        <td class="py-3 text-sm text-gray-900 text-right">
                            {{ number_format($item->unit_price_cents / 100, 2) }}
                        </td>
                        <td class="py-3 text-sm text-gray-600 text-right">
                            {{ $discountAmount > 0 ? '-' . number_format($discountAmount, 2) : '—' }}
                        </td>
                        <td class="py-3 text-sm font-semibold text-gray-900 text-right">
                            {{ number_format($item->total_cents / 100, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ================================================================== --}}
    {{-- TOTALS                                                              --}}
    {{-- ================================================================== --}}
    <div class="px-10 py-6 flex justify-end">
        <div class="w-80">
            <div class="space-y-2">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Subtotal (Excl. VAT)</span>
                    <span class="font-semibold text-gray-900">KES
                        {{ number_format(($order->total_cents - $order->tax_cents) / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span>VAT Amount (16%)</span>
                    <span class="font-semibold text-gray-900">KES {{ number_format($order->tax_cents / 100, 2) }}</span>
                </div>
                @if ($order->shipping_cents > 0)
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Shipping & Delivery</span>
                        <span class="font-semibold text-gray-900">KES
                            {{ number_format($order->shipping_cents / 100, 2) }}</span>
                    </div>
                @endif
                @if ($order->discount_cents > 0)
                    <div class="flex justify-between text-sm text-red-600">
                        <span>Total Discount</span>
                        <span class="font-semibold">-KES {{ number_format($order->discount_cents / 100, 2) }}</span>
                    </div>
                @endif

                <div class="pt-3 mt-3 border-t-2 border-gray-300 flex justify-between items-center">
                    <span class="text-base font-bold text-gray-900 uppercase">Total Payable</span>
                    <span class="text-xl font-bold text-gray-900">KES
                        {{ number_format($order->total_cents / 100, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- ORDER NOTES                                                         --}}
    {{-- ================================================================== --}}
    @if ($order->customer_note)
        <div class="px-10 py-4 border-t border-gray-200">
            <div class="text-xs font-bold text-gray-500 uppercase mb-1">Order Notes</div>
            <div class="text-sm text-gray-600 italic">
                "{{ $order->customer_note }}"
            </div>
        </div>
    @endif
@endsection
