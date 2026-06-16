<template>
  <Layout body-class="bg-[#eef1f6] font-sans">
    <BrandStyle />
    <Preheader>Your order is confirmed — thank you for your purchase.</Preheader>

    <Container class="mx-auto w-full max-w-[600px] px-3 py-7">
      <Section class="overflow-hidden bg-white">

        <MailHeader>
          <template #icon>
            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.4" class="inline-block h-7 w-7 align-middle"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
          </template>
          <template #title>Thanks for your purchase!</template>
          <template #greeting>
            <p class="m-0 text-[15px] font-bold text-white">Hi <Raw>{{ $customerName }}</Raw>,</p>
            <p class="m-0 mt-1 text-[13px] text-white/70">Your order is confirmed.</p>
          </template>
          <template #intro>
            <p class="m-0 text-[13.5px] leading-[21px] text-white/80">Thank you for shopping with us — we've received your order and it's being prepared. Below are the details of your purchase.</p>
          </template>
        </MailHeader>

        <!-- META -->
        <Section class="bg-white px-9 pt-6 pb-1">
          <Raw>
            <p class="m-0 text-xs text-slate-500">Order No: <span class="font-bold text-brand">#{{ $order->order_number }}</span></p>
            <p class="m-0 mt-2.5 text-[13px] font-extrabold text-slate-900">Order placed</p>
            <p class="m-0 mt-0.5 text-[13px] text-slate-500">{{ $order->created_at->format('d M Y') }}</p>
          </Raw>
        </Section>

        <!-- ITEMS TABLE -->
        <Section class="bg-white px-9 pt-4 pb-0">
          <Raw>
            <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td class="border-b-2 border-slate-200 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Item</td>
                <td class="border-b-2 border-slate-200 pb-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Qty</td>
                <td class="border-b-2 border-slate-200 pb-2 text-right text-[11px] font-bold uppercase tracking-wider text-slate-400">Price</td>
              </tr>
              @foreach ($order->items as $item)
                @php
                    $imageUrl = ($coverUrl = $item->product_snapshot['cover_url'] ?? $item->product?->cover_url) ? url($coverUrl) : null;
                    $productName = $item->product_name ?? 'Product';
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
                            @if ($productUrl)<a href="{{ $productUrl }}"><img src="{{ $imageUrl }}" width="48" height="48" alt="{{ $productName }}" class="block h-12 w-12 rounded-md border border-slate-200 object-cover" /></a>@else<img src="{{ $imageUrl }}" width="48" height="48" alt="{{ $productName }}" class="block h-12 w-12 rounded-md border border-slate-200 object-cover" />@endif
                          @else
                            <div class="h-12 w-12 rounded-md border border-slate-200 bg-slate-100"></div>
                          @endif
                        </td>
                        <td class="align-middle">
                          @if ($productUrl)
                            <a href="{{ $productUrl }}" class="m-0 text-sm font-bold leading-snug text-slate-800 no-underline">{{ $productName }}</a>
                          @else
                            <p class="m-0 text-sm font-bold leading-snug text-slate-800">{{ $productName }}</p>
                          @endif
                          @if ($productSku)<p class="m-0 mt-1 text-xs text-slate-400">SKU {{ $productSku }}</p>@endif
                        </td>
                      </tr>
                    </table>
                  </td>
                  <td class="border-b border-slate-100 py-3 text-center align-middle text-[13px] text-slate-600">{{ $item->quantity }}</td>
                  <td class="whitespace-nowrap border-b border-slate-100 py-3 pl-3 text-right align-middle text-sm font-bold text-slate-900">{{ money($item->line_total_cents) }}</td>
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
                      <td class="whitespace-nowrap py-1 text-right text-[13px] font-semibold text-slate-700">{{ money($order->subtotal_cents) }}</td>
                    </tr>
                    @if ($order->delivery_cents > 0)
                      <tr>
                        <td class="py-1 text-[13px] text-slate-500">Delivery</td>
                        <td class="whitespace-nowrap py-1 text-right text-[13px] font-semibold text-slate-700">{{ money($order->delivery_cents) }}</td>
                      </tr>
                    @endif
                    @if ($order->vat_cents > 0)
                      <tr>
                        <td class="py-1 text-[13px] text-slate-500">VAT</td>
                        <td class="whitespace-nowrap py-1 text-right text-[13px] font-semibold text-slate-700">{{ money($order->vat_cents) }}</td>
                      </tr>
                    @endif
                    <tr>
                      <td class="border-t-2 border-slate-200 pt-2.5 text-[15px] font-extrabold text-slate-900">Total</td>
                      <td class="whitespace-nowrap border-t-2 border-slate-200 pt-2.5 text-right text-[15px] font-extrabold text-brand">{{ money($order->total_cents) }}</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </Raw>
        </Section>

        <!-- PAYMENT + SHIPPING -->
        <Section class="bg-white px-9 pt-6 pb-7">
          <Raw>
            <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td class="align-top" style="width: 50%;">
                  <p class="m-0 text-xs font-extrabold text-slate-900">Payment method</p>
                  <p class="m-0 mt-1.5 text-[13px] text-slate-500">{{ $paymentLabel }}</p>
                </td>
                <td class="align-top" style="width: 50%;">
                  <p class="m-0 text-xs font-extrabold text-slate-900">Shipping details</p>
                  @if ($order->address)
                    <p class="m-0 mt-1.5 text-[13px] text-slate-500">{{ $order->address->name }}</p>
                    <p class="m-0 mt-0.5 text-[13px] text-slate-500">{{ $order->address->line1 }}</p>
                    @if ($order->address->phone)<p class="m-0 mt-0.5 text-[13px] text-slate-500">{{ $order->address->phone }}</p>@endif
                  @else
                    <p class="m-0 mt-1.5 text-[13px] text-slate-500">In-store collection</p>
                  @endif
                </td>
              </tr>
            </table>
          </Raw>
        </Section>

        <!-- CTA -->
        <Section class="bg-white px-9 pt-7 pb-1 text-center">
          <Button :href="'{{ $orderUrl }}'" class="rounded-md bg-brand px-8 py-3 text-sm font-bold text-white">Track your order</Button>
        </Section>

        <!-- CLOSING -->
        <Section class="bg-white px-9 pt-6 pb-10">
          <Raw>
            <p class="m-0 text-[13px] leading-5 text-slate-500">If you have any questions, just reply to this email or contact our team.</p>
            <p class="m-0 mt-3.5 text-[13px] text-slate-600">Best regards,<br /><span class="font-bold text-slate-900">{{ config('app.name') }}</span></p>
          </Raw>
        </Section>

        <MailFooter />
      </Section>
    </Container>
  </Layout>
</template>
