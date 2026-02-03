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

        // If address exists but no shipping method selected → go to shipping options
        if (!$user->defaultAddress->hasSelectedShippingMethod()) {
            return redirect()->route('checkout.shipping-options');
        }
    }

    #[Computed]
    public function defaultAddress()
    {
        return auth()->user()->defaultAddress;
    }

    #[Computed]
    public function selectedShippingMethod()
    {
        return $this->defaultAddress?->selectedShippingMethod;
    }

    #[Computed]
    public function selectedShippingRate()
    {
        return $this->defaultAddress?->selectedShippingRate;
    }

    public function changeAddress()
    {
        return redirect()->route('checkout.addresses.create');
    }

    public function changeShippingMethod()
    {
        return redirect()->route('checkout.shipping-options');
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

        <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Customer Address Section -->
                <div class="border rounded-lg bg-white">
                    <div class="px-4 py-3 border-b flex items-center justify-between">
                        <flux:heading level="3" class="font-medium!" size="lg">Delivery Address</flux:heading>

                        <flux:button size="sm" variant="ghost" wire:click="changeAddress"
                            class="text-blue-600 hover:text-blue-700">
                            Change
                            <flux:icon.chevron-right class="size-4 inline-block ml-1" />
                        </flux:button>
                    </div>
                    <div class="px-4 py-5">
                        @if ($this->defaultAddress)
                            <div class="space-y-1">
                                <flux:text class="font-semibold text-lg">{{ $this->defaultAddress->full_name }}
                                </flux:text>
                                <flux:text class="text-zinc-700">{{ $this->defaultAddress->address }}</flux:text>

                                @if ($this->defaultAddress->additional_information)
                                    <flux:text class="text-zinc-600 text-sm">
                                        {{ $this->defaultAddress->additional_information }}
                                    </flux:text>
                                @endif

                                <flux:text class="text-zinc-700">
                                    @if ($this->defaultAddress->area)
                                        {{ $this->defaultAddress->area->name }},
                                    @endif
                                    {{ $this->defaultAddress->county->name }}
                                </flux:text>

                                <div class="pt-2 space-y-1">
                                    <flux:text class="text-zinc-700">
                                        <flux:icon.phone class="size-4 inline-block mr-1" />
                                        {{ $this->defaultAddress->phone_number }}
                                    </flux:text>

                                    @if ($this->defaultAddress->alternative_phone_number)
                                        <flux:text class="text-zinc-600 text-sm">
                                            Alt: {{ $this->defaultAddress->alternative_phone_number }}
                                        </flux:text>
                                    @endif
                                </div>

                                <!-- Shipping Zone Badge -->
                                @if ($this->defaultAddress->shippingZone)
                                    <div class="pt-2">
                                        <flux:badge size="sm" color="zinc">
                                            Zone: {{ $this->defaultAddress->shippingZone->name }}
                                        </flux:badge>
                                    </div>
                                @endif
                            </div>
                        @else
                            <flux:text>You have not set a default address</flux:text>
                        @endif
                    </div>
                </div>

                <!-- Delivery Method Section -->
                <div class="bg-white rounded-lg border">
                    <div class="px-4 py-3 border-b flex items-center justify-between">
                        <flux:heading level="3" class="font-medium!" size="lg">Delivery Method</flux:heading>

                        <flux:button size="sm" variant="ghost" wire:click="changeShippingMethod"
                            class="text-blue-600 hover:text-blue-700">
                            Change
                            <flux:icon.chevron-right class="size-4 inline-block ml-1" />
                        </flux:button>
                    </div>

                    <div class="px-4 py-5">
                        @if ($this->selectedShippingMethod && $this->selectedShippingRate)
                            <div class="flex items-start gap-4">
                                <!-- Method Icon -->
                                @if ($this->selectedShippingMethod->icon)
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                                            <flux:icon :name="$this->selectedShippingMethod->icon"
                                                class="size-6 text-blue-600" />
                                        </div>
                                    </div>
                                @endif

                                <!-- Method Details -->
                                <div class="flex-1">
                                    <flux:text class="font-semibold text-lg mb-1">
                                        {{ $this->selectedShippingMethod->name }}
                                    </flux:text>

                                    @if ($this->selectedShippingMethod->description)
                                        <flux:text class="text-zinc-600 text-sm mb-2">
                                            {{ $this->selectedShippingMethod->description }}
                                        </flux:text>
                                    @endif

                                    <div class="flex items-center gap-4 mt-2">
                                        <!-- Delivery Time -->
                                        @if ($this->selectedShippingRate->estimated_delivery)
                                            <div class="flex items-center gap-1 text-sm text-zinc-600">
                                                <flux:icon.clock class="size-4" />
                                                <span>{{ $this->selectedShippingRate->estimated_delivery }}</span>
                                            </div>
                                        @endif

                                        <!-- Weight Range -->
                                        <div class="flex items-center gap-1 text-sm text-zinc-600">
                                            <flux:icon.cube class="size-4" />
                                            <span>{{ $this->selectedShippingRate->min_weight }}-{{ $this->selectedShippingRate->max_weight }}
                                                KG</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price -->
                                <div class="flex-shrink-0 text-right">
                                    <flux:text class="text-sm text-zinc-600 mb-1">Shipping Cost</flux:text>
                                    <flux:text class="text-xl font-bold text-blue-600">
                                        KES {{ number_format($this->selectedShippingRate->price, 2) }}
                                    </flux:text>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <flux:text class="text-zinc-600">No shipping method selected</flux:text>
                                <flux:button size="sm" wire:click="changeShippingMethod" class="mt-3">
                                    Select Shipping Method
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- TODO: Cart Items Section -->
                <div class="bg-white rounded-lg border">
                    <div class="px-4 py-3 border-b">
                        <flux:heading level="3" class="font-medium!" size="lg">Order Items</flux:heading>
                    </div>
                    <div class="px-4 py-5">
                        <flux:text class="text-zinc-600">Cart items will be displayed here</flux:text>
                    </div>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white border rounded-lg p-4 sticky top-4">
                    <flux:heading level="3" class="font-semibold mb-4">Order Summary</flux:heading>

                    <div class="space-y-3 mb-4 pb-4 border-b">
                        <!-- TODO: Add cart calculations -->
                        <div class="flex justify-between text-sm">
                            <flux:text class="text-zinc-600">Subtotal</flux:text>
                            <flux:text class="font-medium">KES 0.00</flux:text>
                        </div>

                        @if ($this->selectedShippingRate)
                            <div class="flex justify-between text-sm">
                                <flux:text class="text-zinc-600">Shipping</flux:text>
                                <flux:text class="font-medium">KES
                                    {{ number_format($this->selectedShippingRate->price, 2) }}</flux:text>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-between mb-6">
                        <flux:text class="font-semibold text-lg">Total</flux:text>
                        <flux:text class="font-bold text-xl">
                            KES
                            {{ $this->selectedShippingRate ? number_format($this->selectedShippingRate->price, 2) : '0.00' }}
                        </flux:text>
                    </div>

                    <flux:button class="w-full" :disabled="!$this->selectedShippingMethod">
                        Proceed to Payment
                    </flux:button>

                    <div class="mt-4 pt-4 border-t">
                        <div class="flex items-center gap-2 text-sm text-zinc-600">
                            <flux:icon.shield-check class="size-5 text-green-600" />
                            <flux:text>Secure checkout</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
