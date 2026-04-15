<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Tax Invoice {{ $order->reference }}</title>

    @vite('resources/css/app.css')

</head>

<body class="antialiased h-screen text-sm text-zinc-700 tracking-tight font-sans">
    @php
        $logoPath = public_path('logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
    @endphp

    {{-- Header --}}
    <div class="w-full bg-linear-to-t from-zinc-200 via-white">
        <div class="container flex justify-between items-center w-full mx-auto p-8">
            <h1 class="text-2xl font-bold text-zinc-900">Tax Invoice</h1>

            <img src="{{ asset('logo.png') }}" alt="Logo" class="h-12 w-auto ">
        </div>
    </div>

    <div class="container mx-auto p-8 flex items-center">

        <div class="border">
            <div class="px-3 py-2 border-b">
                <h2 class="text-lg font-bold text-zinc-900">Customer Information</h2>
            </div>

            <div class="p-3">
                <p class="text-sm text-zinc-700"><span class="font-bold">Name:</span> {{ $order->customerName() }}</p>
                <p class="text-sm text-zinc-700"><span class="font-bold">Email:</span> {{ $order->customerEmail() }}</p>
                @if ($order->customerPhone())
                    <p class="text-sm text-zinc-700"><span class="font-bold">Phone:</span> {{ $order->customerPhone() }}
                    </p>
                @endif
                @if ($order->billing_address)
                    <div class="mt-2">
                        <p class="text-sm text-zinc-700 font-bold">Billing Address:</p>
                        <p class="text-sm text-zinc-700">
                            {{ $order->billing_address['street_address'] ?? '' }}<br>
                            {{ $order->billing_address['city'] ?? '' }}, {{ $order->billing_address['state'] ?? '' }}
                        </p>
                    </div>
                @endif
                @if ($order->shipping_address)
                    <div class="mt-2">
                        <p class="text-sm text-zinc-700 font-bold">Shipping Address:</p>
                        <p class="text-sm text-zinc-700">
                            {{ $order->shipping_address['street_address'] ?? '' }}<br>
                            {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }}
                        </p>
                    </div>
                @endif
            </div>

        </div>

    </div>

    <div class="p-0">
        {{-- TOP ACCENT BAR --}}
        <div class="h-1.5 bg-brand w-full"></div>

        {{-- HEADER SECTION --}}
        <div class="px-10 py-8 flex justify-between items-start">
            <div class="flex flex-col gap-4">
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo" class="h-12 w-auto">
                @else
                    <div class="text-2xl font-bold text-brand tracking-tight">SHEFFIELD</div>
                @endif

                <div class="mt-2">
                    <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Tax Invoice</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-gray-500 font-medium">Invoice No:</span>
                        <span class="text-gray-900 font-bold">{{ $order->reference }}</span>
                    </div>
                </div>
            </div>

            <div class="text-right">
                <div class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-2">Supplier</div>
                <div class="text-base font-extrabold text-brand uppercase">Sheffield Steel Systems Limited</div>
                <div class="text-gray-600 space-y-0.5 mt-1">
                    <div>Off Old Mombasa Road</div>
                    <div>Opposite Hilton Garden Inn</div>
                    <div>P.O. Box 48670-00100</div>
                    <div>Nairobi, Kenya</div>
                    <div class="pt-1 font-medium text-gray-700">PIN: P051148391Z</div>
                </div>
            </div>
        </div>

        {{-- INFO GRID --}}
        <div class="px-10 py-6 grid grid-cols-3 gap-8 border-y border-gray-100 bg-gray-50/50">
            {{-- Billing To --}}
            <div class="space-y-2">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Bill To</div>
                <div class="space-y-1">
                    <div class="font-bold text-gray-900 text-sm">{{ $order->customerName() }}</div>
                    <div class="text-gray-600">
                        @if ($order->billing_address)
                            {{ $order->billing_address['street_address'] ?? '' }}<br>
                            {{ $order->billing_address['city'] ?? '' }}, {{ $order->billing_address['state'] ?? '' }}
                        @else
                            {{ $order->customerEmail() }}
                        @endif
                    </div>
                    @if ($order->customerPhone())
                        <div class="text-gray-600">Tel: {{ $order->customerPhone() }}</div>
                    @endif
                </div>
            </div>

            {{-- Shipping To --}}
            <div class="space-y-2 border-l border-gray-200 pl-8">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Ship To</div>
                <div class="space-y-1">
                    @if ($order->shipping_address)
                        <div class="font-bold text-gray-900 text-sm">
                            {{ $order->shipping_address['name'] ?? $order->customerName() }}</div>
                        <div class="text-gray-600">
                            {{ $order->shipping_address['street_address'] ?? '' }}<br>
                            {{ $order->shipping_address['city'] ?? '' }},
                            {{ $order->shipping_address['state'] ?? '' }}
                        </div>
                    @else
                        <div class="text-gray-500 italic">Same as billing</div>
                    @endif
                </div>
            </div>

            {{-- Document Meta --}}
            <div class="space-y-3 border-l border-gray-200 pl-8">
                <div class="grid grid-cols-2 gap-x-2 gap-y-3">
                    <div>
                        <div class="text-[9px] font-bold text-gray-400 uppercase">Date Issued</div>
                        <div class="font-semibold text-gray-900">{{ $order->created_at->format('d M, Y') }}</div>
                    </div>
                    <div>
                        <div class="text-[9px] font-bold text-gray-400 uppercase">Payment Status</div>
                        <div
                            class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $order->payment_status->value === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $order->payment_status->label() }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[9px] font-bold text-gray-400 uppercase">Order Ref</div>
                        <div class="font-semibold text-gray-900">#{{ $order->id }}</div>
                    </div>
                    <div>
                        <div class="text-[9px] font-bold text-gray-400 uppercase">Currency</div>
                        <div class="font-semibold text-gray-900">KES</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE SECTION --}}
        <div class="px-10 py-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-900">
                        <th class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter w-8">#</th>
                        <th class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter">Description /
                            Item Code</th>
                        <th
                            class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter text-center w-16">
                            Qty</th>
                        <th
                            class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter text-right w-24">
                            Unit Price</th>
                        <th
                            class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter text-right w-24">
                            Discount</th>
                        <th
                            class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter text-right w-20">
                            VAT %</th>
                        <th
                            class="py-3 text-[10px] font-black text-gray-900 uppercase tracking-tighter text-right w-32">
                            Amount (Inc. VAT)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($order->items as $index => $item)
                        @php
                            $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                            $sku = $item->product_snapshot['sku'] ?? '—';
                            $taxPercentage =
                                $item->unit_price_cents > 0
                                    ? ($item->unit_tax_cents / $item->unit_price_cents) * 100
                                    : 16.0;
                            $discountAmount = $item->discount_cents / 100;
                        @endphp
                        <tr>
                            <td class="py-4 text-gray-500 font-medium align-top">{{ $index + 1 }}</td>
                            <td class="py-4 align-top">
                                <div class="font-bold text-gray-900 text-xs">{{ $name }}</div>
                                <div class="text-[10px] text-brand font-semibold mt-0.5 tracking-wider">
                                    {{ $sku }}</div>
                            </td>
                            <td class="py-4 text-center text-gray-900 font-medium align-top">{{ $item->quantity }}</td>
                            <td class="py-4 text-right text-gray-600 align-top">
                                {{ number_format($item->unit_price_cents / 100, 2) }}</td>
                            <td class="py-4 text-right text-gray-500 align-top">
                                {{ $discountAmount > 0 ? '-' . number_format($discountAmount, 2) : '—' }}
                            </td>
                            <td class="py-4 text-right text-gray-600 align-top">{{ number_format($taxPercentage, 0) }}%
                            </td>
                            <td class="py-4 text-right font-bold text-gray-900 align-top">
                                {{ number_format($item->total_cents / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- SUMMARY & KRA DATA SECTION --}}
        <div class="px-10 py-6 flex justify-between items-start gap-12 bg-gray-50/30 border-t border-gray-100">
            {{-- KRA / Compliance Info --}}
            <div class="flex-1">
                @if ($order->kra_cu_number)
                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm relative overflow-hidden">
                        <div
                            class="absolute top-0 right-0 px-2 py-0.5 bg-green-600 text-[8px] font-black text-white uppercase tracking-widest">
                            eTIMS Validated</div>

                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Compliance
                            Details</div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div>
                                    <div class="text-[9px] text-gray-500 uppercase font-bold">Control Unit (CU) Number
                                    </div>
                                    <div
                                        class="text-[11px] font-mono font-bold text-gray-900 break-all tracking-tighter">
                                        {{ $order->kra_cu_number }}</div>
                                </div>
                                <div>
                                    <div class="text-[9px] text-gray-500 uppercase font-bold">Validation Timestamp</div>
                                    <div class="text-[11px] font-bold text-gray-900">
                                        {{ $order->kra_validated_at?->format('d M Y, H:i:s') ?? $order->created_at->format('d M Y, H:i:s') }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end items-center px-2">
                                {{-- Placeholder for QR --}}
                                <div
                                    class="w-24 h-24 border-2 border-gray-100 p-1.5 bg-white flex flex-col items-center justify-center text-center rounded-lg shadow-inner">
                                    <div
                                        class="w-full h-full bg-[url('https://www.qr-code-generator.com/wp-content/themes/qr/new_structure/markets/core_market/generator/dist/generator/assets/images/website_qr.png')] bg-cover opacity-20 grayscale">
                                    </div>
                                    <div
                                        class="absolute text-[8px] text-gray-400 font-black uppercase tracking-widest">
                                        KRA QR</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 text-gray-400 py-4">
                        <div class="w-1 h-10 bg-gray-200"></div>
                        <div class="italic text-[10px] leading-relaxed">
                            This is an electronically generated Tax Invoice issued in accordance with the Value Added
                            Tax Act of Kenya.
                            The digital signature and control unit data serve as proof of fiscal validation.
                        </div>
                    </div>
                @endif
            </div>

            {{-- Totals --}}
            <div class="w-72">
                <div class="space-y-2.5">
                    <div class="flex justify-between text-[11px] text-gray-500 uppercase font-bold tracking-tighter">
                        <span>Subtotal (Excl. VAT)</span>
                        <span class="text-gray-900">KES
                            {{ number_format(($order->total_cents - $order->tax_cents) / 100, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-[11px] text-gray-500 uppercase font-bold tracking-tighter">
                        <span>VAT Amount (16%)</span>
                        <span class="text-gray-900">KES {{ number_format($order->tax_cents / 100, 2) }}</span>
                    </div>
                    @if ($order->shipping_cents > 0)
                        <div
                            class="flex justify-between text-[11px] text-gray-500 uppercase font-bold tracking-tighter">
                            <span>Shipping & Delivery</span>
                            <span class="text-gray-900">{{ number_format($order->shipping_cents / 100, 2) }}</span>
                        </div>
                    @endif
                    @if ($order->discount_cents > 0)
                        <div
                            class="flex justify-between text-[11px] text-red-600 uppercase font-bold tracking-tighter">
                            <span>Total Discount</span>
                            <span>-{{ number_format($order->discount_cents / 100, 2) }}</span>
                        </div>
                    @endif

                    <div class="pt-4 mt-1 border-t-2 border-gray-900 flex justify-between items-center">
                        <span class="text-sm font-black text-gray-900 uppercase">Total Payable</span>
                        <span class="text-2xl font-black text-brand tracking-tighter">KES
                            {{ number_format($order->total_cents / 100, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- BANKING & FOOTER --}}
        <div class="px-10 py-10 mt-6 bg-gray-900 text-white rounded-t-[40px] relative overflow-hidden">
            {{-- Decorative accent --}}
            <div class="absolute top-0 right-0 w-64 h-64 bg-brand opacity-10 rounded-full -mr-32 -mt-32"></div>

            <div class="grid grid-cols-5 gap-8 relative z-10">
                <div class="col-span-3">
                    <h4
                        class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-4 flex items-center gap-2">
                        <span class="w-8 h-px bg-gray-700"></span>
                        Official Bank Details
                    </h4>
                    <div class="grid grid-cols-2 gap-x-8 gap-y-5">
                        <div class="group">
                            <span
                                class="block text-gray-500 uppercase font-bold text-[8px] mb-1 tracking-widest">Primary
                                Bank (KES)</span>
                            <div class="text-[11px] font-bold text-white border-l-2 border-brand pl-3">
                                Standard Chartered Bank<br>
                                <span class="text-gray-400 font-medium">A/C No:</span> 01020304050607<br>
                                <span class="text-gray-400 font-medium">Branch:</span> Westlands
                            </div>
                        </div>
                        <div>
                            <span class="block text-gray-500 uppercase font-bold text-[8px] mb-1 tracking-widest">MPESA
                                Paybill</span>
                            <div class="text-[11px] font-bold text-white border-l-2 border-pink-600 pl-3">
                                Business No: <span class="text-white">522522</span><br>
                                Account No: <span class="text-white">1128266994</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-2 text-right">
                    <h4
                        class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-4 flex items-center gap-2 justify-end">
                        Direct Support
                        <span class="w-8 h-px bg-gray-700"></span>
                    </h4>
                    <div class="text-[11px] text-gray-300 space-y-2">
                        <div class="flex flex-col">
                            <span class="text-[8px] text-gray-500 uppercase font-bold">Hotline</span>
                            <span class="text-white font-bold">+254 713 444 000</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[8px] text-gray-500 uppercase font-bold">Email</span>
                            <span
                                class="text-white font-bold italic underline decoration-brand underline-offset-4">control@sheffieldafrica.com</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-gray-800 flex justify-between items-center">
                <div class="max-w-md">
                    <div class="text-[9px] text-gray-500 uppercase font-black tracking-widest mb-1">Legal Notice</div>
                    <div class="text-[9px] text-gray-400 leading-relaxed font-medium">
                        Goods remain the property of <span class="text-gray-300">Sheffield Steel Systems Limited</span>
                        until full payment is confirmed.
                        Interest at market rates may be charged on overdue accounts. Claims regarding discrepancies must
                        be submitted in writing within 24 hours.
                    </div>
                </div>

                <div class="text-right">
                    <div class="inline-block text-center border-2 border-gray-800 p-3 rounded-xl">
                        <div class="text-[14px] font-black text-white italic tracking-tighter leading-none mb-1">
                            Sheffield Africa</div>
                        <div class="text-[8px] text-brand uppercase font-black tracking-[0.3em]">Driven. Trusted.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
