<template>
  <Layout body-class="bg-[#eef1f6] font-sans">
    <BrandStyle />
    <Preheader><Raw>Your quotation {{ $quote->quote_number }} is expiring soon — act now.</Raw></Preheader>

    <Container class="mx-auto w-full max-w-[600px] px-3 py-7">
      <Section class="overflow-hidden bg-white">

        <MailHeader>
          <template #icon>
            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.6" class="inline-block h-7 w-7 align-middle"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
          </template>
          <template #title>Your quote expires in <Raw>{{ $daysLeft }}</Raw> days</template>
          <template #greeting>
            <p class="m-0 text-[15px] font-bold text-white">Hi <Raw>{{ $customerName }}</Raw>,</p>
            <p class="m-0 mt-1 text-[13px] text-white/70">Quote <Raw>{{ $quote->quote_number }}</Raw> — act soon.</p>
          </template>
          <template #intro>
            <p class="m-0 text-[13.5px] leading-[21px] text-white/80">Just a quick reminder that the pricing we've prepared for you is still available — but not for long.</p>
          </template>
        </MailHeader>

        <!-- STEPPER -->
        <Section class="bg-white px-9 pt-7 pb-1">
          <QuoteStepper />
        </Section>

        <!-- ITEMS TABLE -->
        <Section class="bg-white px-9 pt-6 pb-0">
          <Raw>
            <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td class="border-b-2 border-slate-200 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Item</td>
                <td class="border-b-2 border-slate-200 pb-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Qty</td>
                <td class="border-b-2 border-slate-200 pb-2 text-right text-[11px] font-bold uppercase tracking-wider text-slate-400">Price</td>
              </tr>
              @foreach ($quote->items as $item)
                @php
                    $imageUrl = ($coverUrl = $item->product_snapshot['cover_url'] ?? $item->product?->cover_url) ? url($coverUrl) : null;
                    $variantLabel = collect($item->product_snapshot['variant']['attributes'] ?? [])->map(fn ($v, $k) => "$k: $v")->join(', ');
                    $subtitle = $variantLabel ?: ($item->product_sku ? 'SKU '.$item->product_sku : null);
                    $productSlug = $item->product_snapshot['slug'] ?? ($item->product?->slug ?? null);
                    $productUrl = $productSlug ? route('product.show', $productSlug) : null;
                @endphp
                <tr>
                  <td class="border-b border-slate-100 py-3 pr-3 align-middle">
                    <table cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td class="pr-3 align-middle" style="width: 48px;">
                          @if ($imageUrl)
                            @if ($productUrl)<a href="{{ $productUrl }}"><img src="{{ $imageUrl }}" width="48" height="48" alt="{{ $item->product_name }}" class="block h-12 w-12 rounded-md border border-slate-200 object-cover" /></a>@else<img src="{{ $imageUrl }}" width="48" height="48" alt="{{ $item->product_name }}" class="block h-12 w-12 rounded-md border border-slate-200 object-cover" />@endif
                          @else
                            <div class="h-12 w-12 rounded-md border border-slate-200 bg-slate-100"></div>
                          @endif
                        </td>
                        <td class="align-middle">
                          @if ($productUrl)<a href="{{ $productUrl }}" class="m-0 text-sm font-bold leading-snug text-slate-800 no-underline">{{ $item->product_name }}</a>@else<p class="m-0 text-sm font-bold leading-snug text-slate-800">{{ $item->product_name }}</p>@endif
                          @if ($subtitle)<p class="m-0 mt-1 text-xs text-slate-400">{{ $subtitle }}</p>@endif
                        </td>
                      </tr>
                    </table>
                  </td>
                  <td class="border-b border-slate-100 py-3 text-center align-middle text-[13px] text-slate-600">{{ $item->quantity }}</td>
                  <td class="whitespace-nowrap border-b border-slate-100 py-3 pl-3 text-right align-middle text-sm font-bold text-slate-900">{{ $item->line_total_cents > 0 ? money($item->line_total_cents) : '—' }}</td>
                </tr>
              @endforeach
            </table>
          </Raw>
        </Section>

        <!-- TOTALS + pill -->
        <Section class="bg-white px-9 pt-5 pb-0">
          <Raw>
            <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td></td>
                <td class="align-top" style="width: 240px;">
                  <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                      <td class="py-1 text-[13px] text-slate-500">Subtotal</td>
                      <td class="whitespace-nowrap py-1 text-right text-[13px] font-semibold text-slate-700">{{ money($quote->subtotal_cents) }}</td>
                    </tr>
                    @if ($quote->discount_cents > 0)
                      <tr>
                        <td class="py-1 text-[13px] text-slate-500">Discount</td>
                        <td class="whitespace-nowrap py-1 text-right text-[13px] font-semibold text-red-600">−{{ money($quote->discount_cents) }}</td>
                      </tr>
                    @endif
                    @if ($quote->shipping_cents > 0)
                      <tr>
                        <td class="py-1 text-[13px] text-slate-500">Shipping</td>
                        <td class="whitespace-nowrap py-1 text-right text-[13px] font-semibold text-slate-700">{{ money($quote->shipping_cents) }}</td>
                      </tr>
                    @endif
                    <tr>
                      <td class="border-t-2 border-slate-200 pt-2.5 text-[15px] font-extrabold text-slate-900">Total{{ $quote->currency ? ' ('.$quote->currency.')' : '' }}</td>
                      <td class="whitespace-nowrap border-t-2 border-slate-200 pt-2.5 text-right text-[15px] font-extrabold text-brand">{{ money($quote->total_cents) }}</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
            <div class="mt-4 rounded-md bg-brand-tint px-5 py-3 text-center">
              <p class="m-0 text-sm text-slate-600"><span class="text-xs font-bold uppercase tracking-wider text-brand">Expires</span> <span class="ml-1 font-bold text-brand">{{ $quote->expires_at?->format('d M Y') }}</span></p>
            </div>
          </Raw>
        </Section>


        <!-- CTA -->
        <Section class="bg-white px-9 pt-7 pb-1 text-center">
          <Button :href="'{{ $portalUrl }}'" class="rounded-md bg-brand px-8 py-3 text-sm font-bold text-white">Review &amp; accept now</Button>
        </Section>

        <!-- CLOSING -->
        <Section class="bg-white px-9 pt-6 pb-10">
          <Raw>
            <p class="m-0 text-[13px] leading-5 text-slate-500">Need adjustments before the offer expires? Just reply to this email — we're happy to help.</p>
            <p class="m-0 mt-3.5 text-[13px] text-slate-600">Warm regards,<br /><span class="font-bold text-slate-900">The {{ config('app.name') }} Team</span></p>
          </Raw>
        </Section>

        <MailFooter />
      </Section>
    </Container>
  </Layout>
</template>
