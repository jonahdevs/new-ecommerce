<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\ShippingMethod;

new #[Layout('layouts.guest')] class extends Component {
    public $selectedMethodId = null;
    public $selectedRateId = null;
    public $cartWeight = 0; // You'll need to calculate this from cart

    public function mount()
    {
        $address = auth()->user()->defaultAddress;

        // If no address, redirect to create address
        if (!$address) {
            return redirect()->route('checkout.addresses.create');
        }

        // Load previously selected method if exists
        $this->selectedMethodId = $address->selected_shipping_method_id;
        $this->selectedRateId = $address->selected_shipping_rate_id;

        // TODO: Calculate cart weight from cart items
        $this->cartWeight = $this->calculateCartWeight();
    }

    #[Computed]
    public function address()
    {
        return auth()->user()->defaultAddress;
    }

    #[Computed]
    public function availableMethods()
    {
        if (!$this->address) {
            return collect();
        }

        $zone = $this->address->shippingZone;

        if (!$zone) {
            return collect();
        }

        // Get all active shipping methods that have rates for this zone
        return ShippingMethod::whereHas('rates', function ($query) use ($zone) {
            $query->where('shipping_zone_id', $zone->id)->where('is_active', true)->where('min_weight', '<=', $this->cartWeight)->where('max_weight', '>=', $this->cartWeight);
        })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($method) use ($zone) {
                // Get the appropriate rate for this method and weight
                $rate = $zone->getRateForMethod($method->id, $this->cartWeight);
                $method->current_rate = $rate;
                return $method;
            })
            ->filter(fn($method) => $method->current_rate !== null);
    }

    public function selectMethod($methodId)
    {
        $this->selectedMethodId = $methodId;

        // Find the rate for this method
        $method = $this->availableMethods->firstWhere('id', $methodId);

        if ($method && $method->current_rate) {
            $this->selectedRateId = $method->current_rate->id;
        }
    }

    public function saveAndContinue()
    {
        $this->validate([
            'selectedMethodId' => 'required|exists:shipping_methods,id',
            'selectedRateId' => 'required|exists:shipping_rates,id',
        ]);

        // Save selected shipping method to address
        $this->address->update([
            'selected_shipping_method_id' => $this->selectedMethodId,
            'selected_shipping_rate_id' => $this->selectedRateId,
        ]);

        // Redirect back to checkout summary
        return redirect()->route('checkout.summary');
    }

    /**
     * Calculate total cart weight
     * TODO: Implement based on your cart system
     */
    private function calculateCartWeight()
    {
        // Example implementation - adjust based on your cart structure
        // return auth()->user()->cartItems()->sum('weight');

        // For now, return a default weight for testing
        return 2.5; // 2.5 KG
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

            <flux:breadcrumbs.item :href="route('checkout.summary')">Checkout</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Shipping Options</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="mx-auto container px-4 py-4 min-h-[80svh]">
        <!-- Shipping Options Header -->
        <flux:heading level="1" class="text-2xl! font-bold!">Select Shipping Method</flux:heading>

        <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Shipping Methods -->
            <div class="lg:col-span-2">
                <!-- Delivery Address Info -->
                <div class="bg-zinc-50 border rounded-lg p-4 mb-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <flux:text class="text-sm text-zinc-600 mb-1">Delivering to:</flux:text>
                            <flux:text class="font-medium">{{ $this->address->full_name }}</flux:text>
                            <flux:text class="text-sm text-zinc-600">{{ $this->address->full_address }}</flux:text>
                            <flux:text class="text-sm text-zinc-600">{{ $this->address->phone_number }}</flux:text>
                        </div>
                        <flux:button size="sm" variant="ghost" href="{{ route('checkout.addresses.create') }}"
                            wire:navigate>
                            Change
                        </flux:button>
                    </div>
                </div>

                <!-- Available Shipping Methods -->
                @if ($this->availableMethods->isEmpty())
                    <div class="bg-white border rounded-lg p-8 text-center">
                        <flux:icon.exclamation-triangle class="size-12 mx-auto text-zinc-400 mb-3" />
                        <flux:heading level="3" class="mb-2">No Shipping Methods Available</flux:heading>
                        <flux:text class="text-zinc-600">
                            Unfortunately, there are no shipping methods available for your location at this time.
                        </flux:text>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($this->availableMethods as $method)
                            <div wire:click="selectMethod({{ $method->id }})"
                                class="bg-white border rounded-lg p-4 cursor-pointer transition-all hover:border-blue-500 {{ $selectedMethodId === $method->id ? 'border-blue-500 ring-2 ring-blue-100' : '' }}">
                                <div class="flex items-start gap-4">
                                    <!-- Radio Button -->
                                    <div class="flex-shrink-0 pt-1">
                                        <div
                                            class="w-5 h-5 rounded-full border-2 flex items-center justify-center {{ $selectedMethodId === $method->id ? 'border-blue-500' : 'border-zinc-300' }}">
                                            @if ($selectedMethodId === $method->id)
                                                <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Method Details -->
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            @if ($method->icon)
                                                <flux:icon :name="$method->icon" class="size-5 text-zinc-600" />
                                            @endif
                                            <flux:heading level="3" class="font-semibold" size="lg">
                                                {{ $method->name }}
                                            </flux:heading>
                                        </div>

                                        @if ($method->description)
                                            <flux:text class="text-sm text-zinc-600 mb-2">
                                                {{ $method->description }}
                                            </flux:text>
                                        @endif

                                        @if ($method->current_rate)
                                            <div class="flex items-center gap-4 text-sm">
                                                @if ($method->current_rate->estimated_delivery)
                                                    <div class="flex items-center gap-1 text-zinc-600">
                                                        <flux:icon.clock class="size-4" />
                                                        <span>{{ $method->current_rate->estimated_delivery }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Price -->
                                    <div class="flex-shrink-0 text-right">
                                        @if ($method->current_rate)
                                            <flux:text class="text-lg font-bold">
                                                KES {{ number_format($method->current_rate->price, 2) }}
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Order Summary Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white border rounded-lg p-4 sticky top-4">
                    <flux:heading level="3" class="font-semibold mb-4">Order Summary</flux:heading>

                    <div class="space-y-3 text-sm mb-4 pb-4 border-b">
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-600">Cart Weight:</flux:text>
                            <flux:text class="font-medium">{{ $cartWeight }} KG</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text class="text-zinc-600">Shipping Zone:</flux:text>
                            <flux:text class="font-medium">{{ $this->address->shippingZone?->name }}</flux:text>
                        </div>
                    </div>

                    @if ($selectedMethodId && $this->availableMethods->firstWhere('id', $selectedMethodId))
                        @php
                            $selectedMethod = $this->availableMethods->firstWhere('id', $selectedMethodId);
                        @endphp
                        <div class="mb-4 pb-4 border-b">
                            <flux:text class="text-sm text-zinc-600 mb-2">Selected Method:</flux:text>
                            <flux:text class="font-medium">{{ $selectedMethod->name }}</flux:text>
                            <flux:text class="text-lg font-bold text-blue-600">
                                KES {{ number_format($selectedMethod->current_rate->price, 2) }}
                            </flux:text>
                        </div>
                    @endif

                    <flux:button wire:click="saveAndContinue" class="w-full" :disabled="!$selectedMethodId">
                        Continue to Payment
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>
