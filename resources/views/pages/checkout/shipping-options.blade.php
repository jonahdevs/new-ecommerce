<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\ShippingMethod;

new #[Layout('layouts.guest')] class extends Component {
    public $selectedMethodId = null;
    public $selectedRateId = null;
    public $cartWeight = 0; // You'll need to calculate this from cart

    public $shippingMethod = 'standard';

    public function mount()
    {
        // If no address, redirect to create address
        if (!$this->defaultAddress()) {
            return redirect()->route('checkout.addresses.create');
        }

        if (auth()->user()->preferredShippingMethod()->exists()) {
            $this->shippingMethod = auth()->user()->preferredShippingMethod->code;
        }
    }

    #[Computed]
    public function defaultAddress()
    {
        return auth()->user()->defaultAddress;
    }

    #[Computed]
    public function availableMethods()
    {
        if (!$this->defaultAddress()) {
            return collect();
        }

        $zone = $this->defaultAddress()->shippingZone;

        if (!$zone) {
            return collect();
        }

        // Get all active shipping methods that have rates for this zone
        return ShippingMethod::active()
            ->where(function ($query) use ($zone) {
                $query->where('code', 'pickup')->orWhereHas('rates', function ($query) use ($zone) {
                    $query->where('shipping_zone_id', $zone->id)->where('is_active', true)->where('min_weight', '<=', $this->cartWeight)->where('max_weight', '>=', $this->cartWeight);
                });
            })
            ->orderBy('sort_order')
            ->get()
            ->map(function ($method) use ($zone) {
                // Get the appropriate rate for this method and weight
                $rate = $zone->getRateForMethod($method->id, $this->cartWeight);
                $method->current_rate = $rate;
                return $method;
            })
            ->filter(fn($method) => $method->code === 'pickup' || $method->current_rate !== null);
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

    public function saveShippingMethod()
    {
        $validated = $this->validate([
            'shippingMethod' => 'required|exists:shipping_methods,code',
        ]);

        try {
            auth()
                ->user()
                ->update([
                    'preferred_shipping_method_id' => ShippingMethod::where('code', $validated['shippingMethod'])->value('id'),
                ]);

            $this->dispatch('shipping-method-selected');
            $this->redirectRoute('checkout.summary', navigate: true);
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to update the shipping method');
        }
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

            <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>Checkout</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Shipping methods</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="mx-auto container px-4 py-4 min-h-[80svh]">
        <!-- Shipping Options Header -->
        <flux:heading level="1" class="text-2xl! font-bold!">Shipping Methods</flux:heading>

        <div class="mt-4 grid grid-cols-1 lg:grid-cols-4 gap-6">

            <div class="lg:col-span-3 space-y-4">

                <!-- Customer Address Section -->
                <div class="border rounded-sm bg-white">
                    <div class="px-4 py-2 border-b flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <flux:icon.check-circle variant="solid" @class([
                                'size-5',
                                'text-green-500' => $this->defaultAddress,
                                'text-zinc-500' => !$this->defaultAddress,
                            ]) />
                            <flux:heading level="3" class="font-medium!">Customer Address</flux:heading>
                        </div>

                        <flux:link :href="route('checkout.addresses')" wire:navigate icon:trailing="chevron-right"
                            class="text-sm! group">Change
                            <flux:icon.chevron-right
                                class="size-4 ms-1 inline-block transition-transform group-hover:translate-x-2" />
                        </flux:link>
                    </div>

                    <div class="px-4 py-5">
                        @if (isset($this->defaultAddress))
                            <flux:heading>{{ $this->defaultAddress->full_name }}
                            </flux:heading>

                            <div class="text-zinc-500 text-sm mt-3 space-y-1">
                                <flux:text>{{ $this->defaultAddress->address }}</flux:text>

                                <flux:text>
                                    {{ implode(
                                        ' | ',
                                        array_filter([
                                            $this->defaultAddress->area?->name . ', ' . $this->defaultAddress->county->name,
                                            $this->defaultAddress->phone_number,
                                        ]),
                                    ) }}
                                </flux:text>
                            </div>
                        @else
                            <p>You have not set a default address</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white border rounded-sm">
                    <div class="px-4 py-2 border-b flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <flux:icon.check-circle variant="solid" @class([
                                'size-5',
                                'text-green-500' => auth()->user()->preferredShippingMethod()->exists(),
                                'text-zinc-500' => auth()->user()->preferredShippingMethod()->doesntExist(),
                            ]) />
                            <flux:heading level="3" class="font-medium!">Delivery Details</flux:heading>
                        </div>
                    </div>

                    <div class="p-5">
                        <!-- Available Shipping Methods -->
                        @if ($this->availableMethods->isEmpty())
                            <div class="p-8 text-center">
                                <flux:icon.exclamation-triangle class="size-12 mx-auto text-zinc-400 mb-3" />
                                <flux:heading level="3" class="mb-2">No Shipping Methods Available</flux:heading>
                                <flux:text class="text-zinc-600">
                                    Unfortunately, there are no shipping methods available for your location at this
                                    time.
                                </flux:text>
                            </div>
                        @else
                            <form wire:submit="saveShippingMethod">
                                <div class="space-y-4">
                                    <flux:radio.group wire:model="shippingMethod" label="Shipping Methods"
                                        variant="cards" class="max-sm:flex-col">
                                        @foreach ($this->availableMethods as $method)
                                            <flux:radio :value="$method->code" :label="$method->name"
                                                :description="$method->description" />
                                        @endforeach
                                    </flux:radio.group>
                                </div>

                                <div class="flex items-center justify-end mt-4">
                                    <flux:button type="submit" variant="primary" size="sm" class="cursor-pointer">
                                        Confirm Delivery Details</flux:button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

                <flux:link :href="route('products')" wire:navigate class="text-xs">Go back & continue shopping
                </flux:link>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="col-span-1">
                <livewire:order-summary />
            </div>
        </div>
    </div>
</div>
