@extends('pdf.browsershot.layouts.main')

@section('title', 'Quotation ' . $quote->reference)

@section('content')
    @php
        $general = app(\App\Settings\GeneralSettings::class);
        $tax = app(\App\Settings\TaxSettings::class);
        $quotationSettings = app(\App\Settings\QuotationSettings::class);

        $logoPath = public_path('logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $companyAddressLines = array_filter([
            $general->store_address,
            $general->store_address_line_2,
            trim(
                implode(', ', array_filter([$general->store_city, $general->store_state, $general->store_postal_code])),
            ),
            $general->store_country,
        ]);

        $hasTax = $quote->tax_cents > 0;
        $currency = $quote->currency;
        $taxRate = null;
        if ($hasTax && $quote->subtotal_cents > 0) {
            $taxRate = round(($quote->tax_cents / $quote->subtotal_cents) * 100);
        }
    @endphp

    {{-- ================================================================== --}}
    {{-- HEADER — Company info (left) + QUOTATION + Date/Number (right)     --}}
    {{-- ================================================================== --}}
    <div class="px-10 pt-8 pb-3">
        <div class="flex justify-between items-start gap-6">

            {{-- LEFT: Logo stacked over contact details. The logo already carries the brand --}}
            {{-- name so we don't repeat it; address/phone/email flow directly under it. --}}
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

            {{-- RIGHT: QUOTATION + Date/Number table --}}
            {{-- border-collapse: collapse merges adjacent cell borders into a single uniform 1px line --}}
            {{-- so the vertical divider between DATE and NUMBER is the same thickness as every other edge. --}}
            <div class="text-right shrink-0">
                <div class="text-xl font-bold text-gray-900 tracking-wide mb-2">QUOTATION</div>
                <table class="text-xs ml-auto" style="border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="border border-gray-900 px-3 py-1 font-bold text-gray-900 bg-gray-50"
                                style="text-align: center;">DATE</th>
                            <th class="border border-gray-900 px-3 py-1 font-bold text-gray-900 bg-gray-50"
                                style="text-align: center;">NUMBER</th>
                        </tr>
                    </thead>
                    <tbody class="mt-2">
                        <tr>
                            <td class="border border-gray-900 px-3 py-1 bg-white" style="text-align: center;">
                                {{ ($quote->quoted_at ?? $quote->created_at)->format('d/m/Y') }}
                            </td>
                            <td class="border border-gray-900 px-3 py-1 bg-white" style="text-align: center;">
                                {{ $quote->reference }}
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
    {{-- QUOTATION TO: — compact bordered card on the left, no full-width    --}}
    {{-- ================================================================== --}}
    <div class="px-10 mt-5">
        <div class="text-xs font-bold text-gray-900 mb-1.5">QUOTATION TO:</div>
        <div class="inline-block border border-gray-900 px-3 py-2 text-[11px] leading-snug min-w-[14rem] max-w-xs"
            style="box-shadow: 3px 3px 0 rgba(0,0,0,0.85);">
            <div class="font-bold uppercase text-gray-900">{{ $quote->customerName() }}</div>
            @if ($quote->customerPhone())
                <div>TEL: {{ $quote->customerPhone() }}</div>
            @endif
            @if ($quote->customerEmail())
                <div>EMAIL: {{ $quote->customerEmail() }}</div>
            @endif
            @if ($quote->preferred_county || $quote->preferred_area)
                <div class="uppercase">
                    {{ implode(', ', array_filter([$quote->preferred_area, $quote->preferred_county])) }}
                </div>
            @endif
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- ITEMS TABLE                                                        --}}
    {{-- ================================================================== --}}
    <div class="px-10 mt-5">
        <table class="w-full border-collapse text-xs">
            <thead>
                <tr>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 w-12 text-left">ITEM
                    </th>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 text-center">DETAILS
                    </th>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 w-20 text-right">PRICE
                    </th>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 w-12 text-center">QTY
                    </th>
                    <th class="border border-gray-400 px-2 py-2 bg-white font-bold text-gray-900 w-24 text-right">AMOUNT
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quote->items as $index => $item)
                    @php
                        $name = $item->product_snapshot['name'] ?? ($item->product?->name ?? '—');
                        $sku = $item->product_snapshot['sku'] ?? null;
                        $brand = $item->product_snapshot['brand'] ?? null;
                        $variantAttrs = $item->product_snapshot['variant'] ?? null;
                        $shortDesc = $item->product?->short_description;
                        $unitPrice = ($item->quoted_price_cents ?? $item->original_price_cents) / 100;
                        // Amount is always price × quantity — total_cents may be 0 on freshly-created quotes.
                        $lineAmount = $unitPrice * $item->quantity;
                    @endphp
                    <tr>
                        <td class="border border-gray-400 px-2 py-2 align-top text-left">{{ $index + 1 }}.</td>
                        <td class="border border-gray-400 px-2 py-2 align-top">
                            <div class="font-bold text-gray-900 underline">{{ strtoupper($name) }}</div>
                            <ul class="mt-1 ml-4 list-disc text-[11px] text-gray-800 space-y-0.5">
                                @if ($brand)
                                    <li>Brand: {{ $brand }}</li>
                                @endif
                                @if ($sku)
                                    <li>SKU: {{ $sku }}</li>
                                @endif
                                @if (is_array($variantAttrs))
                                    @foreach ($variantAttrs as $attr => $value)
                                        <li>{{ $attr }}: {{ $value }}</li>
                                    @endforeach
                                @endif
                                @if ($shortDesc)
                                    <li>{{ \Illuminate\Support\Str::limit(strip_tags($shortDesc), 140) }}</li>
                                @endif
                            </ul>
                        </td>
                        <td class="border border-gray-400 px-2 py-2 align-top text-right">
                            {{ number_format($unitPrice, 2) }}
                        </td>
                        <td class="border border-gray-400 px-2 py-2 align-top text-center">{{ $item->quantity }}</td>
                        <td class="border border-gray-400 px-2 py-2 align-top text-right font-semibold">
                            {{ number_format($lineAmount, 2) }}
                        </td>
                    </tr>
                @endforeach

                {{-- Subtotal --}}
                <tr>
                    <td colspan="4" class="border border-gray-400 px-2 py-2 text-right text-gray-700">Subtotal</td>
                    <td class="border border-gray-400 px-2 py-2 text-right font-semibold">
                        {{ number_format($quote->subtotal_cents / 100, 2) }}
                    </td>
                </tr>

                {{-- Discount (only if any) --}}
                @if ($quote->discount_cents > 0)
                    <tr>
                        <td colspan="4" class="border border-gray-400 px-2 py-2 text-right text-gray-700">Discount</td>
                        <td class="border border-gray-400 px-2 py-2 text-right">
                            -{{ number_format($quote->discount_cents / 100, 2) }}
                        </td>
                    </tr>
                @endif

                {{-- Shipping (only if any) --}}
                @if ($quote->shipping_cents > 0)
                    <tr>
                        <td colspan="4" class="border border-gray-400 px-2 py-2 text-right text-gray-700">Estimated
                            Shipping</td>
                        <td class="border border-gray-400 px-2 py-2 text-right">
                            {{ number_format($quote->shipping_cents / 100, 2) }}
                        </td>
                    </tr>
                @endif

                {{-- Tax / VAT (only if any) --}}
                @if ($hasTax)
                    <tr>
                        <td colspan="4" class="border border-gray-400 px-2 py-2 text-right text-gray-700">
                            {{ $taxRate ? "{$taxRate}% " : '' }}{{ $tax->tax_name ?? 'VAT' }}
                        </td>
                        <td class="border border-gray-400 px-2 py-2 text-right">
                            {{ number_format($quote->tax_cents / 100, 2) }}
                        </td>
                    </tr>
                @endif

                {{-- Total --}}
                <tr>
                    <td colspan="4" class="border border-gray-400 px-2 py-2 text-right font-bold text-base">
                        TOTAL ({{ $currency }})
                    </td>
                    <td class="border border-gray-400 px-2 py-2 text-right font-bold text-base">
                        {{ number_format($quote->total_cents / 100, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- VAT / PIN registration numbers under the table --}}
        @if ($tax->tax_registration_number ?? null)
            <div class="mt-1 text-[10px] text-gray-800 leading-snug">
                {{ strtoupper($tax->tax_name ?? 'VAT') }} REG NO. {{ $tax->tax_registration_number }}
            </div>
        @endif
    </div>

    {{-- ================================================================== --}}
    {{-- CUSTOMER NOTES                                                      --}}
    {{-- ================================================================== --}}
    @if ($quote->customer_notes)
        <div class="px-10 mt-5">
            <div class="text-xs font-bold text-gray-700 uppercase mb-1">Customer Notes</div>
            <div class="text-xs text-gray-700 italic">"{{ $quote->customer_notes }}"</div>
        </div>
    @endif

    {{-- ================================================================== --}}
    {{-- TERMS — admin-set or default from QuotationSettings                --}}
    {{-- ================================================================== --}}
    @if (!empty($quote->admin_notes ?: $quotationSettings->quote_terms))
        <div class="px-10 mt-5">
            <div class="text-xs font-bold text-gray-700 uppercase mb-1">Terms & Conditions</div>
            <div class="text-[11px] text-gray-700 leading-snug whitespace-pre-line">
                {{ $quote->admin_notes ?: $quotationSettings->quote_terms }}</div>
        </div>
    @endif

    {{-- ================================================================== --}}
    {{-- VALIDITY                                                            --}}
    {{-- ================================================================== --}}
    @if ($quote->expires_at && $quote->status->value === 'sent')
        <div class="px-10 mt-3 text-[11px] text-gray-700">
            <strong>Note:</strong> This quotation is valid until {{ $quote->expires_at->format('d M, Y') }}. Prices and
            availability are subject to change after this date.
        </div>
    @endif

    {{-- ================================================================== --}}
    {{-- FOOTER NOTE — optional admin-set footer text, no brand name        --}}
    {{-- ================================================================== --}}
    @if ($quotationSettings->quote_footer_note)
        <div class="mt-8 mb-4 text-center">
            <div class="text-[10px] text-gray-600">{{ $quotationSettings->quote_footer_note }}</div>
        </div>
    @endif
@endsection
