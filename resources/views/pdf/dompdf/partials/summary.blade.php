<div class="px-10 py-6 flex justify-between items-start gap-12 bg-gray-50/30 border-t border-gray-100">
    {{-- KRA / Compliance Info --}}
    <div class="flex-1">
        @if ($order->kra_cu_number)
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 px-2 py-0.5 bg-green-600 text-[8px] font-black text-white uppercase tracking-widest">eTIMS Validated</div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Compliance Details</div>
                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <div class="text-[9px] text-gray-500 uppercase font-bold">Control Unit (CU) Number</div>
                        <div class="text-[11px] font-mono font-bold text-gray-900 break-all tracking-tighter">{{ $order->kra_cu_number }}</div>
                    </div>
                    <div>
                        <div class="text-[9px] text-gray-500 uppercase font-bold">Validation Timestamp</div>
                        <div class="text-[11px] font-bold text-gray-900">{{ $order->kra_validated_at?->format('d M Y, H:i:s') ?? $order->created_at->format('d M Y, H:i:s') }}</div>
                    </div>
                </div>
            </div>
        @else
            <div class="flex items-center gap-3 text-gray-400 py-4">
                <div class="w-1 h-10 bg-gray-200"></div>
                <div class="italic text-[10px] leading-relaxed">
                    This is an electronically generated Tax Invoice issued in accordance with the Value Added Tax Act of Kenya.
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
                <span class="text-gray-900">KES {{ number_format(($order->total_cents - $order->tax_cents) / 100, 2) }}</span>
            </div>
            <div class="flex justify-between text-[11px] text-gray-500 uppercase font-bold tracking-tighter">
                <span>VAT Amount (16%)</span>
                <span class="text-gray-900">KES {{ number_format($order->tax_cents / 100, 2) }}</span>
            </div>
            @if ($order->shipping_cents > 0)
                <div class="flex justify-between text-[11px] text-gray-500 uppercase font-bold tracking-tighter">
                    <span>Shipping & Delivery</span>
                    <span class="text-gray-900">{{ number_format($order->shipping_cents / 100, 2) }}</span>
                </div>
            @endif
            @if ($order->discount_cents > 0)
                <div class="flex justify-between text-[11px] text-red-600 uppercase font-bold tracking-tighter">
                    <span>Total Discount</span>
                    <span>-{{ number_format($order->discount_cents / 100, 2) }}</span>
                </div>
            @endif

            <div class="pt-4 mt-1 border-t-2 border-gray-900 flex justify-between items-center">
                <span class="text-sm font-black text-gray-900 uppercase">Total Payable</span>
                <span class="text-2xl font-black text-[#c02434] tracking-tighter">KES {{ number_format($order->total_cents / 100, 2) }}</span>
            </div>
        </div>
    </div>
</div>
