<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Services\CartService;

new #[Layout('layouts.guest')] class extends Component {
    public function mount()
    {
        $user = auth()->user();
        $cartService = app(CartService::class);

        if (!$cartService->getCart()->items()->exists()) {
            $this->redirectRoute('cart');
        }

        // If no address exists → go to create address
        if ($user->defaultAddress()->doesntExist()) {
            return redirect()->route('checkout.addresses.create');
        }

        // If address exists but no shipping method selected → go to shipping options
        if (auth()->user()->preferredShippingMethod()->doesntExist()) {
            return redirect()->route('checkout.shipping-options');
        }
    }

    #[Computed]
    public function defaultAddress()
    {
        $user = auth()->user();

        $sessionAddress = session('checkout_address_id');

        // Use session address if exists and valid, otherwise use default address
        if ($sessionAddress && $user->addresses()->where('id', $sessionAddress)->exists()) {
            return $user->addresses()->where('id', $sessionAddress)->first();
        } else {
            return auth()->user()->defaultAddress;
        }
    }

    #[Computed]
    public function preferredShippingMethod()
    {
        return auth()->user()->preferredShippingMethod;
    }

    #[Computed]
    public function selectedShippingRate()
    {
        return $this->defaultAddress?->selectedShippingRate;
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
                            class="text-xs! group">Change
                            <flux:icon.chevron-right
                                class="size-3.5 ms-1 inline-block transition-transform group-hover:translate-x-2" />
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

                <!-- Delivery Method Section -->
                <div class="bg-white rounded-sm border">
                    <div class="px-4 py-2 border-b flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <flux:icon.check-circle variant="solid" @class([
                                'size-5',
                                'text-green-500' => auth()->user()->preferredShippingMethod()->exists(),
                                'text-zinc-500' => auth()->user()->preferredShippingMethod()->doesntExist(),
                            ]) />
                            <flux:heading level="3" class="font-medium!">Delivery Details</flux:heading>
                        </div>

                        <flux:link :href="route('checkout.shipping-options')" wire:navigate
                            icon:trailing="chevron-right" class="text-xs! group">Change
                            <flux:icon.chevron-right
                                class="size-3.5 inline-block ms-1 group-hover:translate-x-2 transition-transform" />
                        </flux:link>
                    </div>

                    <div class="px-4 py-5">
                        @if ($this->preferredShippingMethod && $this->preferredShippingMethod)
                            <div class="flex items-center justify-between">
                                <div>

                                    <flux:heading>{{ $this->preferredShippingMethod->name }}</flux:heading>

                                    <flux:text>{{ $this->preferredShippingMethod->description }}</flux:text>
                                </div>

                                <flux:icon :name="$this->preferredShippingMethod->icon" class="shrink-0" />

                            </div>
                        @else
                            <div class="text-center py-4">
                                <flux:text class="text-zinc-600">No shipping method selected</flux:text>
                            </div>
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
