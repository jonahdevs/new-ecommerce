<?php

use App\Models\Address;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\OrderSummaryService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function summary()
    {
        return app(OrderSummaryService::class)->summary();
    }
};

?>

<div class="border bg-white rounded-sm sticky top-44">
    <div class="px-3 py-2 border-b">
        <flux:heading>Order Summary</flux:heading>
    </div>
    <div class="p-5 flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <flux:text>
                <flux:icon.receipt class="text-inherit size-4 inline-block me-1" />
                Subtotal
            </flux:text>
            <flux:heading>{{ $this->summary['subtotal'] }}</flux:heading>
        </div>

        <div class="flex items-center justify-between">
            <flux:text>
                <flux:icon.badge-percent class="text-inherit size-4 inline-block me-1" />
                Discount
            </flux:text>
            <flux:heading>{{ $this->summary['discount'] }}</flux:heading>
        </div>

        <div class="flex items-center justify-between">
            <flux:text>
                <flux:icon.truck class="text-inherit size-4 inline-block me-1" />
                Shipping
            </flux:text>
            <flux:heading>{{ $this->summary['shipping_cost'] }}</flux:heading>
        </div>
    </div>

    <div class="flex items-center justify-between border-t px-3 py-2">
        <flux:text class="font-semibold text-base">Total</flux:text>
        <flux:heading class="font-semibold text-base">{{ $this->summary['total'] }}</flux:heading>
    </div>

    <div class="p-3 border-t">
        <flux:button class="w-full group cursor-pointer" variant="primary">Proceed to Checkout
            <x-slot name="iconTrailing">
                <flux:icon.chevron-right class="size-4 ms-3 group-hover:translate-x-1 transition-transform" />
            </x-slot>
        </flux:button>
    </div>
</div>
