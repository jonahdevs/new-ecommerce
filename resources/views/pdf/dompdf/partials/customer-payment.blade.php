<div class="px-10 py-6 grid grid-cols-3 gap-8 border-y border-gray-100 bg-gray-50/50">
    {{-- Billing To --}}
    <div class="space-y-2">
        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Bill To</div>
        <div class="space-y-1">
            <div class="font-bold text-gray-900 text-sm">{{ $order->customerName() }}</div>
            <div class="text-gray-600 text-xs leading-relaxed">
                @if ($order->billing_address)
                    {{ $order->billing_address['street_address'] ?? '' }}<br>
                    {{ $order->billing_address['city'] ?? '' }}, {{ $order->billing_address['state'] ?? '' }}
                @else
                    {{ $order->customerEmail() }}
                @endif
            </div>
            @if ($order->customerPhone())
                <div class="text-gray-600 text-xs">Tel: {{ $order->customerPhone() }}</div>
            @endif
        </div>
    </div>

    {{-- Shipping To --}}
    <div class="space-y-2 border-l border-gray-200 pl-8">
        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Ship To</div>
        <div class="space-y-1">
            @if ($order->shipping_address)
                <div class="font-bold text-gray-900 text-sm">
                    {{ $order->shipping_address['full_name'] ?? $order->customerName() }}</div>
                <div class="text-gray-600 text-xs leading-relaxed">
                    {{ $order->shipping_address['address'] ?? '' }}<br>
                    {{ implode(', ', array_filter([
                        $order->shipping_address['area'] ?? null,
                        $order->shipping_address['county'] ?? null
                    ])) }}
                </div>
                @if(isset($order->shipping_address['phone_number']))
                    <div class="text-gray-600 text-xs">Tel: {{ $order->shipping_address['phone_number'] }}</div>
                @endif
            @else
                <div class="text-gray-500 italic text-xs">Same as billing</div>
            @endif
        </div>
    </div>

    {{-- Document Meta --}}
    <div class="space-y-3 border-l border-gray-200 pl-8">
        <div class="grid grid-cols-2 gap-x-2 gap-y-4">
            <div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Date Issued</div>
                <div class="font-bold text-gray-900 text-xs">{{ $order->created_at->format('d M, Y') }}</div>
            </div>
            <div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Payment Status</div>
                <div class="mt-0.5">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider {{ $order->payment_status->value === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $order->payment_status->label() }}
                    </span>
                </div>
            </div>
            <div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Payment Method</div>
                <div class="font-bold text-gray-900 text-xs">{{ $paymentLabel ?? ($order->payment?->gateway_label ?? 'Online Payment') }}</div>
            </div>
            <div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Currency</div>
                <div class="font-bold text-gray-900 text-xs">KES</div>
            </div>
        </div>
    </div>
</div>
