<template>
  <Layout body-class="bg-[#eef1f6] font-sans">
    <BrandStyle />
    <Preheader><Raw>We've received your quote request {{ $quote->quote_number }} — our team is on it.</Raw></Preheader>

    <Container class="mx-auto w-full max-w-[600px] px-3 py-7">
      <Section class="overflow-hidden bg-white">

        <MailHeader>
          <template #icon>
            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.6" class="inline-block h-7 w-7 align-middle"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86c.42 0 .8.24.98.62l.82 1.76c.18.38.56.62.98.62h4.32c.42 0 .8-.24.98-.62l.82-1.76c.18-.38.56-.62.98-.62h3.86M2.25 13.5V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.5M2.25 13.5l1.7-7a2.25 2.25 0 0 1 2.19-1.72h11.72a2.25 2.25 0 0 1 2.19 1.72l1.71 7" /></svg>
          </template>
          <template #title>Request received</template>
          <template #greeting>
            <p class="m-0 text-[15px] font-bold text-white">Hi <Raw>{{ $customerName }}</Raw>,</p>
            <p class="m-0 mt-1 text-[13px] text-white/70">Your request is in good hands.</p>
          </template>
          <template #intro>
            <p class="m-0 text-[13.5px] leading-[21px] text-white/80">We've received your quotation request and our team is already on it. We'll send you a detailed, priced quotation within <Text as="span" class="font-bold text-white">1 business day</Text>.</p>
          </template>
        </MailHeader>

        <!-- REFERENCE + FULFILMENT -->
        <Section class="bg-white px-9 pt-6 pb-0">
          <Raw>
            @php $isPickup = ! $quote->delivery_required; @endphp
            <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td class="align-middle">
                  <p class="m-0 text-[10px] font-bold uppercase tracking-wider text-slate-400">Quote reference</p>
                  <p class="m-0 mt-1 text-lg font-extrabold text-brand">{{ $quote->quote_number }}</p>
                </td>
                <td class="text-right align-middle">
                  <p class="m-0 text-[10px] font-bold uppercase tracking-wider text-slate-400">Fulfilment</p>
                  @if ($isPickup)<p class="m-0 mt-1 text-[13px] font-semibold text-slate-700">&#127981; In-store pickup</p>@else<p class="m-0 mt-1 text-[13px] font-semibold text-slate-700">&#128230; Delivery requested</p>@endif
                </td>
              </tr>
            </table>
          </Raw>
        </Section>

        <!-- ITEMS TABLE -->
        <Section class="bg-white px-9 pt-5 pb-0">
          <Raw>
            @php $itemCount = $quote->items->count(); @endphp
            <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td class="border-b-2 border-slate-200 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Item ({{ $itemCount }})</td>
                <td class="border-b-2 border-slate-200 pb-2 text-right text-[11px] font-bold uppercase tracking-wider text-slate-400">Qty</td>
              </tr>
              @foreach ($quote->items as $item)
                @php
                    $imageUrl = ($coverUrl = $item->product_snapshot['cover_url'] ?? $item->product?->cover_url) ? url($coverUrl) : null;
                    $productSku = $item->product_sku ?? '';
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
                          @if ($productSku)<p class="m-0 mt-1 text-xs text-slate-400">SKU {{ $productSku }}</p>@endif
                        </td>
                      </tr>
                    </table>
                  </td>
                  <td class="whitespace-nowrap border-b border-slate-100 py-3 pl-3 text-right align-middle text-[13px] font-semibold text-slate-600">{{ $item->quantity }}</td>
                </tr>
              @endforeach
            </table>
          </Raw>
        </Section>

        <!-- WHAT HAPPENS NEXT -->
        <Section class="bg-white px-9 pt-7 pb-7">
          <Raw>
            @php $isPickup = ! $quote->delivery_required; @endphp
            <p class="m-0 mb-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">What happens next</p>
            <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td class="w-8 align-top"><div class="h-7 w-7 rounded-[9999px] bg-navy text-center text-sm font-bold leading-7 text-white">1</div></td>
                <td class="pb-4 pl-3 align-top">
                  <p class="m-0 text-sm font-semibold text-slate-800">Our team reviews your request</p>
                  <p class="m-0 mt-1 text-[13px] text-slate-500">We check availability, source the best pricing, and @if ($isPickup)prepare your items for collection.@else calculate accurate delivery costs to your location.@endif</p>
                </td>
              </tr>
              <tr>
                <td class="w-8 align-top"><div class="h-7 w-7 rounded-[9999px] bg-navy text-center text-sm font-bold leading-7 text-white">2</div></td>
                <td class="pb-4 pl-3 align-top">
                  <p class="m-0 text-sm font-semibold text-slate-800">You receive your priced quotation</p>
                  <p class="m-0 mt-1 text-[13px] text-slate-500">Within 1 business day you'll get a formal quote @if ($isPickup)with item pricing — no shipping charges, since you'll collect from us.@else including item pricing and delivery costs.@endif</p>
                </td>
              </tr>
              <tr>
                <td class="w-8 align-top"><div class="h-7 w-7 rounded-[9999px] bg-navy text-center text-sm font-bold leading-7 text-white">3</div></td>
                <td class="pl-3 align-top">
                  <p class="m-0 text-sm font-semibold text-slate-800">You accept or request changes</p>
                  <p class="m-0 mt-1 text-[13px] text-slate-500">Happy with the quote? Accept it online and we'll get your order moving. Need adjustments? Just let us know.</p>
                </td>
              </tr>
            </table>
            @if ($quote->notes)
              <div class="mt-5 rounded-r-md border-l-[3px] border-navy bg-slate-50 px-4 py-3">
                <p class="m-0 mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Your notes</p>
                <p class="m-0 text-sm italic text-slate-600">&ldquo;{{ $quote->notes }}&rdquo;</p>
              </div>
            @endif
          </Raw>
        </Section>

        <!-- CTA -->
        <Section class="bg-white px-9 pt-7 pb-1 text-center">
          <Button :href="'{{ $quotationsUrl }}'" class="rounded-md bg-brand px-8 py-3 text-sm font-bold text-white">View my quotations</Button>
        </Section>

        <!-- CLOSING -->
        <Section class="bg-white px-9 pt-6 pb-10">
          <Raw>
            <p class="m-0 text-[13px] leading-5 text-slate-500">You can track your quotation status any time from your account portal.</p>
            <p class="m-0 mt-3.5 text-[13px] text-slate-600">Warm regards,<br /><span class="font-bold text-slate-900">The {{ config('app.name') }} Team</span></p>
          </Raw>
        </Section>

        <MailFooter />
      </Section>
    </Container>
  </Layout>
</template>
