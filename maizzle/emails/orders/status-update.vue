<template>
  <Layout body-class="bg-[#eef1f6] font-sans">
    <BrandStyle />
    <Preheader><Raw>Your order {{ $order->order_number }} has been updated to {{ $newStatus->label() }}.</Raw></Preheader>

    <Container class="mx-auto w-full max-w-[600px] px-3 py-7">
      <Section class="overflow-hidden bg-white">

        <MailHeader>
          <template #icon>
            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.6" class="inline-block h-7 w-7 align-middle"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
          </template>
          <template #title>Order update</template>
          <template #greeting>
            <p class="m-0 text-[15px] font-bold text-white">Hi <Raw>{{ $customerName }}</Raw>,</p>
            <p class="m-0 mt-1 text-[13px] text-white/70">Order <Raw>{{ $order->order_number }}</Raw> — here's the latest.</p>
          </template>
          <template #intro>
            <p class="m-0 text-[13.5px] leading-[21px] text-white/80">Your order is now <Text as="span" class="font-bold text-white"><Raw>{{ $newStatus->label() }}</Raw></Text>. We'll keep you posted as it moves.</p>
          </template>
        </MailHeader>

        <!-- STEPPER -->
        <Section class="bg-white px-9 pt-7 pb-1">
          <Raw>
            @php
                $processingActive   = in_array($newStatus->value, ['processing', 'out_for_delivery', 'completed']);
                $outActive          = in_array($newStatus->value, ['out_for_delivery', 'completed']);
                $completedActive    = $newStatus->value === 'completed';
                $orderUrl = route('account.orders.show', $order);
            @endphp
            <table class="w-full table-fixed" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td style="width: 7.5%;"></td>
                <td style="width: 10%;" class="align-middle"><div class="mx-auto h-9 w-9 rounded-[9999px] bg-navy text-center text-sm font-bold leading-9 text-white">&#10003;</div></td>
                <td style="width: 15%;" class="align-middle">@if ($processingActive)<div class="h-[3px] w-full bg-navy"></div>@else<div class="h-[3px] w-full bg-slate-200"></div>@endif</td>
                <td style="width: 10%;" class="align-middle">
                  @if ($processingActive)<div class="mx-auto h-9 w-9 rounded-[9999px] bg-navy text-center text-sm font-bold leading-9 text-white">&#10003;</div>@else<div class="mx-auto h-9 w-9 rounded-[9999px] border border-slate-200 bg-slate-50 text-center text-sm font-bold leading-9 text-slate-400">2</div>@endif
                </td>
                <td style="width: 15%;" class="align-middle">@if ($outActive)<div class="h-[3px] w-full bg-navy"></div>@else<div class="h-[3px] w-full bg-slate-200"></div>@endif</td>
                <td style="width: 10%;" class="align-middle">
                  @if ($outActive)<div class="mx-auto h-9 w-9 rounded-[9999px] bg-navy text-center text-sm font-bold leading-9 text-white">&#10003;</div>@else<div class="mx-auto h-9 w-9 rounded-[9999px] border border-slate-200 bg-slate-50 text-center text-sm font-bold leading-9 text-slate-400">3</div>@endif
                </td>
                <td style="width: 15%;" class="align-middle">@if ($completedActive)<div class="h-[3px] w-full bg-navy"></div>@else<div class="h-[3px] w-full bg-slate-200"></div>@endif</td>
                <td style="width: 10%;" class="align-middle">
                  @if ($completedActive)<div class="mx-auto h-9 w-9 rounded-[9999px] bg-navy text-center text-sm font-bold leading-9 text-white">&#10003;</div>@else<div class="mx-auto h-9 w-9 rounded-[9999px] border border-slate-200 bg-slate-50 text-center text-sm font-bold leading-9 text-slate-400">4</div>@endif
                </td>
                <td style="width: 7.5%;"></td>
              </tr>
            </table>
            <table class="w-full table-fixed" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td style="width: 25%;"><p class="m-0 mt-2 text-center text-[10px] font-bold uppercase tracking-wide text-navy">Placed</p></td>
                <td style="width: 25%;"><p class="m-0 mt-2 text-center text-[10px] font-bold uppercase tracking-wide @if ($processingActive)text-navy @else text-slate-400 @endif">Processing</p></td>
                <td style="width: 25%;"><p class="m-0 mt-2 text-center text-[10px] font-bold uppercase tracking-wide @if ($outActive)text-navy @else text-slate-400 @endif">Out for delivery</p></td>
                <td style="width: 25%;"><p class="m-0 mt-2 text-center text-[10px] font-bold uppercase tracking-wide @if ($completedActive)text-navy @else text-slate-400 @endif">Completed</p></td>
              </tr>
            </table>
          </Raw>
        </Section>

        <!-- ITEMS TABLE -->
        <Section class="bg-white px-9 pt-6 pb-0">
          <Raw>
            <p class="m-0 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Your order</p>
            <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td class="border-b-2 border-slate-200 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Item</td>
                <td class="border-b-2 border-slate-200 pb-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Qty</td>
                <td class="border-b-2 border-slate-200 pb-2 text-right text-[11px] font-bold uppercase tracking-wider text-slate-400">Price</td>
              </tr>
              @foreach ($order->items as $item)
                @php
                    $imagePath = $item->product_snapshot['image_path'] ?? ($item->product?->image_path ?? null);
                    $imageUrl = $imagePath ? asset('storage/'.$imagePath) : null;
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
                          @if ($productUrl)<a href="{{ $productUrl }}" class="m-0 text-sm font-bold leading-snug text-slate-800 no-underline">{{ $productName }}</a>@else<p class="m-0 text-sm font-bold leading-snug text-slate-800">{{ $productName }}</p>@endif
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
            <table class="mt-1 w-full" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td></td>
                <td class="align-top" style="width: 240px;">
                  <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
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

        <!-- CTA -->
        <Section class="bg-white px-9 pt-7 pb-1 text-center">
          <Button :href="'{{ $orderUrl }}'" class="rounded-md bg-brand px-8 py-3 text-sm font-bold text-white">View your order</Button>
        </Section>

        <!-- CLOSING -->
        <Section class="bg-white px-9 pt-6 pb-10">
          <Raw>
            <p class="m-0 text-[13px] leading-5 text-slate-500">Questions about your order? Just reply to this email — our team is happy to help.</p>
            <p class="m-0 mt-3.5 text-[13px] text-slate-600">Thank you for your order,<br /><span class="font-bold text-slate-900">{{ config('app.name') }} Support Team</span></p>
          </Raw>
        </Section>

        <MailFooter />
      </Section>
    </Container>
  </Layout>
</template>
