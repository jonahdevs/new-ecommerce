<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('layouts.guest')] class extends Component {
    public function mount()
    {
        $user = auth()->user();

        // If no address exists → go to create address
        if ($user->defaultAddress()->doesntExist()) {
            return redirect()->route('checkout.addresses.create');
        }

        // If address exists → go to shipping options
        return redirect()->route('checkout.shipping-options');
    }
    #[Computed]
    public function defaultAddress()
    {
        return auth()->user()->defaultAddress;
    }
};
?>

<div>
    {{-- Breadcrumb --}}
    <div class="bg-zinc-100">
        <flux:breadcrumbs class="container mx-auto py-4 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Checkout</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="mx-auto container px-4 py-4 min-h-[80svh]">
        <!-- Checkout Summary Header -->
        <flux:heading level="1" class="text-2xl! font-bold!">Checkout Summary</flux:heading>

        <div class="mt-4 grid grid-cols-4 gap-6">
            <div class="col-span-3 space-y-6">
                <div class="border rounded-sm bg-white">
                    <div class="px-3 py-2 border-b flex items-center justify-between">
                        <flux:heading level="3" class="font-medium!" size="lg">Customer Address</flux:heading>

                        <flux:link href="#" class="text-sm group" color="blue">
                            Change
                            <flux:icon.chevron-right
                                class="size-4 inline-block group-hover:translate-x-1 transition-transform" />
                        </flux:link>
                    </div>
                    <div class="px-3 py-5">
                        @if (isset($this->defaultAddress))
                            <div>
                                <p class="font-medium text-lg">{{ $this->defaultAddress->full_name }}</p>
                                <p>{{ $this->defaultAddress->address_line_1 }}</p>
                                @if ($this->defaultAddress->address_line_2)
                                    <p>{{ $this->defaultAddress->address_line_2 }}</p>
                                @endif
                                <p>{{ $this->defaultAddress->city }}, {{ $this->defaultAddress->state }}
                                    {{ $this->defaultAddress->postal_code }}</p>
                                <p>{{ $this->defaultAddress->country }}</p>
                                <p class="mt-2">Phone: {{ $this->defaultAddress->phone }}</p>
                            </div>
                        @else
                            <flux:text>You have not set a default address</flux:text>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-sm border">
                    <div class="px-3 py-2 border-b flex items-center justify-between">
                        <flux:heading level="3" class="font-medium!" size="lg">Delivery Details</flux:heading>

                        <flux:link href="#" class="text-sm group" color="blue">
                            Change
                            <flux:icon.chevron-right
                                class="size-4 inline-block group-hover:translate-x-1 transition-transform" />
                        </flux:link>
                    </div>

                    <div class="px-3 py-5">

                    </div>

                </div>
            </div>
            <div class="col-span-1">

            </div>
        </div>

    </div>
</div>
