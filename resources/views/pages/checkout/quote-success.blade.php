<?php

use App\Models\Order;
use Livewire\Attributes\{Layout, Locked};
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component {
    #[Locked]
    public string $reference = '';

    public function mount(string $reference): void
    {
        // Ensure the order belongs to the authenticated user
        $order = Order::where('reference', $reference)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            $this->redirectRoute('home', navigate: true);
            return;
        }

        $this->dispatch('cart-updated');

        $this->reference = $reference;
    }
};
?>

<div>
    <x-slot:heading>Quote Request</x-slot:heading>

    <div class="max-w-lg mx-auto text-center py-10">

        {{-- Success icon --}}
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
            <flux:icon.check class="size-8 text-green-600" />
        </div>

        {{-- Heading --}}
        <flux:heading size="xl" class="mb-2">Quote Request Sent!</flux:heading>

        <flux:text class="text-zinc-500 text-sm mb-1">
            Your order reference is:
        </flux:text>
        <p class="font-mono font-semibold text-zinc-800 text-sm mb-5">
            {{ $reference }}
        </p>

        {{-- What happens next --}}
        <div class="text-left bg-zinc-50 border border-zinc-200 rounded-lg p-4 mb-6 space-y-3">
            <p class="text-sm font-medium text-zinc-800">What happens next:</p>

            <div class="flex items-start gap-3">
                <div class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                    <span class="text-xs font-bold text-amber-600">1</span>
                </div>
                <p class="text-sm text-zinc-600">
                    Our team reviews your order and calculates the delivery cost to your location.
                </p>
            </div>

            <div class="flex items-start gap-3">
                <div class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                    <span class="text-xs font-bold text-amber-600">2</span>
                </div>
                <p class="text-sm text-zinc-600">
                    We contact you via phone or email with the delivery quote.
                </p>
            </div>

            <div class="flex items-start gap-3">
                <div class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                    <span class="text-xs font-bold text-amber-600">3</span>
                </div>
                <p class="text-sm text-zinc-600">
                    Once you confirm, payment is taken and your order is processed.
                </p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-center gap-3">
            <flux:button :href="route('customer.orders.index')" wire:navigate variant="primary" class="cursor-pointer">
                View My Orders
            </flux:button>

            <flux:button :href="route('shop.index')" wire:navigate variant="ghost" class="cursor-pointer">
                Continue Shopping
            </flux:button>
        </div>
    </div>
</div>
