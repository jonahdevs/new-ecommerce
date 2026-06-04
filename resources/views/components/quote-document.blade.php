@props(['quote', 'showActions' => false])

<style>
    @media print {
        /* Prevent a table row from being split across two pages */
        tr { page-break-inside: avoid; }
    }
</style>

@php
    $branding       = app(\App\Settings\BrandingSettings::class);
    $business       = app(\App\Settings\BusinessSettings::class);
    $storeName      = $branding->store_name ?: config('app.name');
    $logoUrl        = $branding->logo_path
                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($branding->logo_path)
                        : '/logo.png';
    // Use the rate and inclusion flag snapshotted at quote save time — the document
    // must reflect the tax terms that were in effect when the quote was prepared,
    // not the current global settings which may have changed since.
    $vatRate          = (float) $quote->vat_rate;
    $pricesIncludeTax = (bool) $quote->tax_inclusive;
    $contactName    = $quote->contact_name ?? $quote->user?->name;
    $contactEmail   = $quote->contact_email ?? $quote->user?->email;
    $contactPhone   = $quote->contact_phone;
    $contactCompany = $quote->contact_company;
    $isExpired      = $quote->expires_at?->isPast();
@endphp

<div class="mx-auto max-w-3xl bg-white font-sans text-[13px] text-zinc-800 shadow-sm border border-zinc-200 rounded-lg overflow-hidden print:shadow-none print:border-0 print:rounded-none">

    {{-- ================================================== --}}
    {{-- HEADER --}}
    {{-- ================================================== --}}
    <div class="px-8 pt-7 pb-4">
        <div class="flex items-start justify-between gap-6">

            {{-- Logo + company details --}}
            <div class="flex-1">
                <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-10 w-auto" />
                <div class="mt-2.5 space-y-0.5 text-[11px] leading-snug text-zinc-500">
                    @if ($business->address)
                        <div>{{ $business->address }}</div>
                    @endif
                    @if ($business->contact_phone)
                        <div>Tel: {{ $business->contact_phone }}</div>
                    @endif
                    @if ($business->contact_email)
                        <div>Email: {{ $business->contact_email }}</div>
                    @endif
                    @if ($business->tax_pin)
                        <div>PIN: {{ $business->tax_pin }}</div>
                    @endif
                </div>
            </div>

            {{-- QUOTATION title + DATE / NUMBER table --}}
            <div class="shrink-0 text-right">
                <div class="text-xl font-bold tracking-widest text-zinc-900 uppercase">Quotation</div>
                <table class="mt-2 ml-auto text-[11px]" style="border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="border border-zinc-900 bg-zinc-50 px-3 py-1 font-bold text-zinc-900 text-center">DATE</th>
                            <th class="border border-zinc-900 bg-zinc-50 px-3 py-1 font-bold text-zinc-900 text-center">NUMBER</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-zinc-900 bg-white px-3 py-1 text-center">
                                {{ $quote->created_at->format('d/m/Y') }}
                            </td>
                            <td class="border border-zinc-900 bg-white px-3 py-1 font-mono text-center font-semibold">
                                {{ $quote->quote_number }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Double-rule separator --}}
    <div class="mx-8 border-t-2 border-zinc-900"></div>
    <div class="mx-8 border-t border-zinc-900 mt-px"></div>

    {{-- ================================================== --}}
    {{-- QUOTATION TO --}}
    {{-- ================================================== --}}
    <div class="px-8 mt-5">
        <div class="text-[10px] font-bold uppercase tracking-widest text-zinc-700 mb-1.5">Quotation to:</div>
        <div class="inline-block border border-zinc-900 px-4 py-2.5 text-[11.5px] leading-snug min-w-[14rem]"
             style="box-shadow: 3px 3px 0 rgba(0,0,0,0.75);">
            @if ($contactName)
                <div class="font-bold uppercase text-zinc-900">{{ $contactName }}</div>
            @endif
            @if ($contactCompany)
                <div class="text-zinc-600">{{ $contactCompany }}</div>
            @endif
            @if ($contactPhone)
                <div>Tel: {{ $contactPhone }}</div>
            @endif
            @if ($contactEmail)
                <div>Email: {{ $contactEmail }}</div>
            @endif
            @if ($quote->delivery_required && $quote->delivery_address)
                <div class="mt-1 pt-1 border-t border-zinc-200 text-zinc-500">
                    Deliver to: {{ $quote->delivery_address }}
                </div>
            @endif
        </div>
    </div>

    {{-- ================================================== --}}
    {{-- ITEMS TABLE --}}
    {{-- ================================================== --}}
    <div class="px-8 mt-6">
        <table class="w-full text-[12px]" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-left w-8">#</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-left">Description</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-center w-12">Qty</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-right w-28">Unit price</th>
                    <th class="border border-zinc-300 bg-zinc-100 px-2 py-2 font-bold text-zinc-900 text-right w-28">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quote->items as $index => $item)
                    <tr>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-zinc-500">{{ $index + 1 }}.</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top">
                            <div class="font-bold uppercase text-zinc-900 underline">{{ $item->product_name }}</div>
                            @if ($item->product_sku)
                                <ul class="mt-1 ml-4 list-disc text-[11px] text-zinc-600 space-y-0.5">
                                    <li>SKU: {{ $item->product_sku }}</li>
                                </ul>
                            @endif
                        </td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-center tabular-nums">{{ $item->quantity }}</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-right tabular-nums text-zinc-600">{!! money($item->unit_price_cents) !!}</td>
                        <td class="border border-zinc-300 px-2 py-2 align-top text-right tabular-nums font-semibold text-zinc-900">{!! money($item->line_total_cents) !!}</td>
                    </tr>
                @endforeach

                {{-- Subtotal --}}
                <tr>
                    <td colspan="4" class="border border-zinc-300 px-2 py-2 text-right text-zinc-500">Subtotal</td>
                    <td class="border border-zinc-300 px-2 py-2 text-right tabular-nums font-semibold">{!! money($quote->subtotal_cents) !!}</td>
                </tr>

                {{-- Discount --}}
                @if ($quote->discount_cents > 0)
                    <tr>
                        <td colspan="4" class="border border-zinc-300 px-2 py-2 text-right text-zinc-500">Discount</td>
                        <td class="border border-zinc-300 px-2 py-2 text-right tabular-nums text-red-600">−{!! money($quote->discount_cents) !!}</td>
                    </tr>
                @endif

                {{-- Shipping (only relevant when customer requested delivery) --}}
                @if ($quote->delivery_required && $quote->shipping_cents > 0)
                    <tr>
                        <td colspan="4" class="border border-zinc-300 px-2 py-2 text-right text-zinc-500">Shipping</td>
                        <td class="border border-zinc-300 px-2 py-2 text-right tabular-nums">{!! money($quote->shipping_cents) !!}</td>
                    </tr>
                @endif

                {{-- VAT --}}
                @if ($vatRate > 0 && $quote->vat_cents > 0)
                    <tr>
                        <td colspan="4" class="border border-zinc-300 px-2 py-2 text-right text-zinc-500">
                            {{ $pricesIncludeTax ? "VAT included ({$vatRate}%)" : "VAT ({$vatRate}%)" }}
                        </td>
                        <td class="border border-zinc-300 px-2 py-2 text-right tabular-nums">{!! money($quote->vat_cents) !!}</td>
                    </tr>
                @endif

                {{-- Grand total --}}
                <tr>
                    <td colspan="4" class="border border-zinc-300 bg-zinc-100 px-2 py-2.5 text-right font-bold text-zinc-900 text-[13px]">
                        Total ({{ $quote->currency }})
                    </td>
                    <td class="border border-zinc-300 bg-zinc-100 px-2 py-2.5 text-right font-bold text-zinc-900 text-[13px] tabular-nums">
                        {!! money($quote->total_cents) !!}
                    </td>
                </tr>

                @if ($quote->delivery_required && $quote->shipping_cents === 0)
                    <tr>
                        <td colspan="5" class="border border-zinc-300 px-2 py-1.5 text-center text-[10.5px] text-amber-700 italic">
                            * Delivery cost not yet included — will be confirmed with the order.
                        </td>
                    </tr>
                @elseif ($vatRate > 0)
                    <tr>
                        <td colspan="5" class="border border-zinc-300 px-2 py-1.5 text-center text-[10.5px] text-zinc-400 italic">
                            @if ($pricesIncludeTax)
                                * All prices are inclusive of VAT at {{ $vatRate }}%
                            @else
                                * VAT at {{ $vatRate }}% is charged in addition to the above prices
                            @endif
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- ================================================== --}}
    {{-- TERMS --}}
    {{-- ================================================== --}}
    @if ($quote->terms)
        <div class="px-8 mt-4">
            <div class="text-[11.5px] text-zinc-600 leading-relaxed whitespace-pre-line">{{ $quote->terms }}</div>
        </div>
    @endif

    {{-- ================================================== --}}
    {{-- VALIDITY NOTE + SIGN-OFF --}}
    {{-- ================================================== --}}
    <div class="px-8 mt-5 text-[11px] text-zinc-600">
        @if ($quote->expires_at && ! $isExpired)
            <p><strong>Note:</strong> This quotation is valid until {{ $quote->expires_at->format('d F Y') }}. Prices and availability are subject to change after this date.</p>
        @endif
        <p class="mt-3">Best regards,<br><strong>{{ $storeName }}</strong></p>
    </div>

    {{-- ================================================== --}}
    {{-- CUSTOMER ACCEPT / DECLINE ACTIONS --}}
    {{-- ================================================== --}}
    @if ($showActions)
        <div class="border-t border-zinc-200 bg-zinc-50 px-8 py-5 mt-6">
            @if ($quote->status === \App\Enums\QuoteStatus::SENT || $quote->status === \App\Enums\QuoteStatus::AWAITING_APPROVAL)
                <div class="flex items-center justify-between gap-4">
                    <p class="text-[12.5px] text-zinc-500">
                        Please review this quotation and let us know if you'd like to proceed.
                    </p>
                    <div class="flex shrink-0 items-center gap-3">
                        <flux:button variant="ghost" size="sm" wire:click="decline">Decline</flux:button>
                        <flux:button variant="customer-primary" size="customer" wire:click="approve">Accept quote</flux:button>
                    </div>
                </div>
            @elseif ($quote->status === \App\Enums\QuoteStatus::APPROVED)
                <div class="flex items-center gap-2 text-emerald-700">
                    <flux:icon.check-circle variant="micro" class="size-4" />
                    <span class="text-[13px] font-medium">You accepted this quote on {{ $quote->updated_at->format('d F Y') }}. Our team will be in touch shortly.</span>
                </div>
            @elseif ($quote->status === \App\Enums\QuoteStatus::DECLINED)
                <p class="text-[13px] text-zinc-400">This quote was declined.</p>
            @endif
        </div>
    @endif

    {{-- ================================================== --}}
    {{-- FOOTER --}}
    {{-- ================================================== --}}
    {{-- ================================================== --}}
    {{-- FOOTER --}}
    {{-- ================================================== --}}
    @php
        $showrooms = \App\Models\Showroom::orderByDesc('is_hq')->orderBy('sort_order')->limit(3)->get();
        $banking   = app(\App\Settings\PaymentSettings::class)->bank_details;
    @endphp

    <div id="quote-footer" class="mt-12 border-t border-zinc-300 bg-white">
        <div class="grid grid-cols-4">

            {{-- One column per showroom --}}
            @foreach ($showrooms as $showroom)
                <div class="px-5 py-4">
                    <div class="mb-2 flex items-center gap-1.5">
                        <span class="text-[9px] font-bold uppercase tracking-widest text-zinc-500">{{ $showroom->city }}</span>
                        @if ($showroom->is_hq)
                            <span class="rounded bg-zinc-800 px-1 py-px text-[7.5px] font-bold uppercase tracking-wide text-white">HQ</span>
                        @endif
                    </div>
                    <div class="space-y-0.5 text-[10.5px] leading-snug text-zinc-600">
                        <div>{{ $showroom->address }}</div>
                        @if ($showroom->pobox)
                            <div>P.O. Box {{ $showroom->pobox }}</div>
                        @endif
                        @if (!empty($showroom->phones))
                            <div>Tel: {{ collect($showroom->phones)->first() }}</div>
                        @endif
                        @if ($showroom->email)
                            <div>{{ $showroom->email }}</div>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Website --}}
            <div class="px-5 py-4">
                <div class="mb-2 text-[9px] font-bold uppercase tracking-widest text-zinc-500">Our Website</div>
                <div class="text-[11px] font-semibold text-zinc-900 break-all">{{ config('app.url') }}</div>
            </div>


        </div>
    </div>

</div>
