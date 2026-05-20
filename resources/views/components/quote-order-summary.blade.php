<?php

use App\Models\Order;
use App\Services\TaxService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $orderId;

    #[Computed]
    public function order(): Order
    {
        return Order::with(['items.product:id,image_path,name'])->findOrFail($this->orderId);
    }

    #[Computed]
    public function taxService(): TaxService
    {
        return app(TaxService::class);
    }
};
?>

<div class="bg-white border border-zinc-200 rounded-sm overflow-hidden">

    {{-- Header --}}
    <div class="px-5 py-4 border-b border-zinc-200 bg-white">
        <h3 class="text-[13px] font-bold uppercase tracking-widest text-on-surface">Order Summary</h3>
        <p class="text-[11px] text-on-surface-variant font-medium mt-0.5">From your accepted quotation</p>
    </div>

    {{-- Items list --}}
    <div class="divide-y divide-zinc-200 max-h-52 overflow-y-auto">
        @foreach ($this->order->items as $item)
            @php
                $imageUrl = $item->product_snapshot['image_url']
                    ?? ($item->product_snapshot['image_path'] ? asset('storage/' . $item->product_snapshot['image_path']) : null)
                    ?? $item->product?->image_url;
                $name = $item->getProductName();
                $sku = $item->getProductSku();
                $variantAttrs = $item->product_snapshot['variant'] ?? [];
            @endphp
            <div class="flex items-center gap-3 px-4 py-3.5">
                <div class="w-12 h-12 rounded border border-zinc-200 bg-zinc-50 overflow-hidden shrink-0">
                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $name }}" class="w-full h-full object-cover" />
                    @else
                        <flux:icon.photo class="w-full h-full p-2 text-zinc-300" />
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-bold text-on-surface truncate mb-0.5">{{ $name }}</p>
                    @if ($sku)
                        <p class="text-[10px] font-bold tracking-widest uppercase text-on-surface-variant mb-0.5">
                            {{ $sku }}
                        </p>
                    @endif
                    @if (!empty($variantAttrs))
                        <p class="text-[11px] text-on-surface-variant truncate font-medium mb-1">
                            {{ collect($variantAttrs)->map(fn($v, $k) => "$k: $v")->join(' · ') }}
                        </p>
                    @endif
                    <p class="text-[11px] text-on-surface-variant font-medium">Qty: {{ $item->quantity }}</p>
                </div>
                <div class="shrink-0">
                    <span class="text-[14px] font-bold text-on-surface">
                        {{ format_currency($item->total) }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Totals --}}
    <div class="px-5 py-4 bg-zinc-50 border-t border-zinc-200 space-y-3">
        <div class="flex justify-between items-center">
            <span class="text-[13px] text-on-surface-variant font-medium">Subtotal</span>
            <span class="text-[14px] text-on-surface font-bold">
                {{ format_currency($this->order->subtotal) }}
            </span>
        </div>

        @if ($this->order->discount_cents > 0)
            <div class="flex justify-between items-center">
                <span class="text-[13px] text-green-600 font-medium">Discount</span>
                <span class="text-[14px] text-green-600 font-bold">
                    − {{ format_currency($this->order->discount) }}
                </span>
            </div>
        @endif

        <div class="flex justify-between items-center">
            <span class="text-[13px] text-on-surface-variant font-medium">Shipping</span>
            @if ($this->order->shipping_cents === 0)
                <span class="text-[14px] text-green-600 font-bold">FREE</span>
            @else
                <span class="text-[14px] text-on-surface font-bold">
                    {{ format_currency($this->order->shipping) }}
                </span>
            @endif
        </div>

        @if ($this->taxService->isEnabled() && $this->order->tax_cents > 0)
            <div class="flex justify-between items-center">
                <span class="text-[13px] text-on-surface-variant font-medium">
                    {{ $this->taxService->name() }}
                    @if ($this->taxService->isInclusive())
                        <span class="text-[11px]">(incl.)</span>
                    @endif
                </span>
                <span class="text-[14px] text-on-surface font-bold">
                    {{ format_currency($this->order->tax) }}
                </span>
            </div>
        @endif

        <div class="pt-3 border-t border-zinc-200 flex justify-between items-baseline">
            <span class="text-[14px] font-bold uppercase tracking-widest text-on-surface">Total</span>
            <span class="text-[22px] font-black text-primary leading-none">
                {{ format_currency($this->order->total) }}
            </span>
        </div>
    </div>

    {{-- CTA slot --}}
    <div class="p-4 border-t border-zinc-200 bg-white">
        {{ $slot }}
    </div>

    {{-- Trust badges --}}
    <div class="py-4 px-5 border-t border-zinc-100">
        <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-3">We accept</div>
        <div class="flex flex-wrap gap-1.5 mb-6">
            @foreach (['VISA', 'MPESA', 'MASTERCARD', 'PAYPAL'] as $pay)
                <span
                    class="inline-block px-2 py-1 bg-zinc-100 border border-zinc-200 rounded text-[9px] font-extrabold text-on-surface-variant tracking-wider">
                    {{ $pay }}
                </span>
            @endforeach
        </div>

        <div class="space-y-3">
            <div class="flex items-center gap-2.5 text-[12px] text-on-surface-variant font-medium">
                <flux:icon.shield-check class="size-4 text-on-surface-variant shrink-0" />
                <span>SSL Encrypted & Secure</span>
            </div>
            <div class="flex items-center gap-2.5 text-[12px] text-on-surface-variant font-medium">
                <flux:icon.arrow-path class="size-4 text-on-surface-variant shrink-0" />
                <span>30-Day Easy Returns Policy</span>
            </div>
        </div>
    </div>
</div>
