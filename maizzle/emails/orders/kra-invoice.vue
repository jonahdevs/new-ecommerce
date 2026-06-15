<template>
  <Layout body-class="bg-[#eef1f6] font-sans">
    <BrandStyle />
    <Preheader><Raw>Your KRA tax invoice for order {{ $order->order_number }} is ready.</Raw></Preheader>

    <Container class="mx-auto w-full max-w-[600px] px-3 py-7">
      <Section class="overflow-hidden bg-white">

        <MailHeader>
          <template #icon>
            <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.6" class="inline-block h-7 w-7 align-middle"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12.75h7.5M8.25 15.75h7.5" /></svg>
          </template>
          <template #title>Your tax invoice is ready</template>
          <template #greeting>
            <p class="m-0 text-[15px] font-bold text-white">Hi <Raw>{{ $customerName }}</Raw>,</p>
            <p class="m-0 mt-1 text-[13px] text-white/70">Your tax invoice is attached.</p>
          </template>
          <template #intro>
            <p class="m-0 text-[13.5px] leading-[21px] text-white/80">The KRA-validated invoice for this order is attached as a PDF — please keep it for your records. Here's a quick summary.</p>
          </template>
        </MailHeader>

        <!-- INVOICE DETAILS -->
        <Section class="bg-white px-9 pt-6 pb-1">
          <Raw>
            <div class="mb-3 inline-block rounded-[9999px] border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-emerald-700">KRA validated</div>
            <table class="w-full" cellpadding="0" cellspacing="0" role="presentation">
              <tr>
                <td class="border-b border-slate-200 py-2 text-[13px] text-slate-500">Order reference</td>
                <td class="border-b border-slate-200 py-2 text-right text-[13px] font-semibold text-slate-700">{{ $order->order_number }}</td>
              </tr>
              @if ($order->kra_cu_number)
                <tr>
                  <td class="border-b border-slate-200 py-2 text-[13px] text-slate-500">KRA CU number</td>
                  <td class="border-b border-slate-200 py-2 text-right text-[13px] font-semibold text-slate-700">{{ $order->kra_cu_number }}</td>
                </tr>
              @endif
              <tr>
                <td class="py-2 text-[13px] text-slate-500">Date</td>
                <td class="py-2 text-right text-[13px] font-semibold text-slate-700">{{ $order->created_at->format('d M Y') }}</td>
              </tr>
              <tr>
                <td class="border-t-2 border-slate-200 pt-2.5 text-[15px] font-extrabold text-slate-900">Total</td>
                <td class="whitespace-nowrap border-t-2 border-slate-200 pt-2.5 text-right text-[15px] font-extrabold text-brand">{{ money($order->total_cents) }}</td>
              </tr>
            </table>
          </Raw>
        </Section>

        <!-- CTA -->
        <Section class="bg-white px-9 pt-7 pb-1 text-center">
          <Button :href="'{{ $orderUrl }}'" class="rounded-md bg-brand px-8 py-3 text-sm font-bold text-white">View order</Button>
        </Section>

        <!-- CLOSING -->
        <Section class="bg-white px-9 pt-6 pb-10">
          <Raw>
            <p class="m-0 text-[13px] leading-5 text-slate-500">Please keep this invoice for your records. Questions? Just reply to this email — we're happy to help.</p>
            <p class="m-0 mt-3.5 text-[13px] text-slate-600">Thank you for choosing {{ config('app.name') }},<br /><span class="font-bold text-slate-900">{{ config('app.name') }} Support Team</span></p>
          </Raw>
        </Section>

        <MailFooter />
      </Section>
    </Container>
  </Layout>
</template>
