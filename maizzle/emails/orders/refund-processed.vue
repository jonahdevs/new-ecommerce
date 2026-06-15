<template>
  <Layout body-class="bg-[#eef1f6] font-sans">
    <BrandStyle />
    <Preheader><Raw>Your refund for order {{ $order->order_number }} has been processed.</Raw></Preheader>

    <Container class="mx-auto w-full max-w-[600px] px-3 py-7">
      <Section class="overflow-hidden bg-white">

        <MailHeader>
          <template #icon>
            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8" class="inline-block h-7 w-7 align-middle"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
          </template>
          <template #title>Refund processed</template>
          <template #greeting>
            <p class="m-0 text-[15px] font-bold text-white">Hi <Raw>{{ $customerName }}</Raw>,</p>
            <p class="m-0 mt-1 text-[13px] text-white/70">Your refund is on its way.</p>
          </template>
          <template #intro>
            <p class="m-0 text-[13.5px] leading-[21px] text-white/80">We've processed your refund. It will be credited back to your original payment method within <Text as="span" class="font-bold text-white">5–10 business days</Text>.</p>
          </template>
        </MailHeader>

        <!-- REFUND DETAILS -->
        <Section class="bg-white px-9 pt-6 pb-1">
          <Raw>
            <p class="m-0 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Refund details</p>
            <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td class="border-b border-slate-200 py-2 text-[13px] text-slate-500">Order reference</td>
                <td class="border-b border-slate-200 py-2 text-right text-[13px] font-semibold text-slate-700">{{ $order->order_number }}</td>
              </tr>
              <tr>
                <td class="border-b border-slate-200 py-2 text-[13px] text-slate-500">Original amount</td>
                <td class="border-b border-slate-200 py-2 text-right text-[13px] font-semibold text-slate-700">{{ money($order->total_cents) }}</td>
              </tr>
              <tr>
                <td class="py-2 text-[13px] font-semibold text-slate-700">Refund amount</td>
                <td class="py-2 text-right text-base font-extrabold text-emerald-600">{{ money($refundAmount) }}</td>
              </tr>
            </table>
            @if ($refundReason)
              <div class="mt-4 rounded-md bg-amber-50 px-5 py-4">
                <p class="m-0 mb-1 text-xs font-semibold uppercase tracking-wider text-amber-700">Reason</p>
                <p class="m-0 text-sm text-amber-900">{{ $refundReason }}</p>
              </div>
            @endif
          </Raw>
        </Section>

        <!-- CTA -->
        <Section class="bg-white px-9 pt-7 pb-1 text-center">
          <Button :href="'{{ $orderUrl }}'" class="rounded-md bg-brand px-8 py-3 text-sm font-bold text-white">View order details</Button>
        </Section>

        <!-- CLOSING -->
        <Section class="bg-white px-9 pt-6 pb-10">
          <Raw>
            <p class="m-0 text-[13px] leading-5 text-slate-500">If you have any questions about this refund, just reply to this email — our team is happy to help.</p>
            <p class="m-0 mt-3.5 text-[13px] text-slate-600">Best regards,<br /><span class="font-bold text-slate-900">{{ config('app.name') }} Support Team</span></p>
          </Raw>
        </Section>

        <MailFooter />
      </Section>
    </Container>
  </Layout>
</template>
