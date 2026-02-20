<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('layouts.checkout')] class extends Component {
    public $selectedAddress = null;

    public function mount()
    {
        $user = auth()->user();

        // If no address exists → go to create address
        if ($user->addresses()->doesntExist()) {
            return redirect()->route('checkout.addresses.create');
        }

        // Check if there's a checkout address in session
        $sessionAddress = session('checkout_address_id');

        // Use session address if exists and valid, otherwise use default address
        if ($sessionAddress && $user->addresses()->where('id', $sessionAddress)->exists()) {
            $this->selectedAddress = $sessionAddress;
        } else {
            $this->selectedAddress = $user->defaultAddress?->id ?? $user->addresses()->first()->id;
        }
    }

    #[Computed]
    public function addresses()
    {
        return auth()->user()->addresses;
    }

    public function selectAddress()
    {
        // Validate that the selected address belongs to the user
        $user = auth()->user();

        if (!$user->addresses()->where('id', $this->selectedAddress)->exists()) {
            $this->addError('selectedAddress', 'Invalid address selected.');
            return;
        }

        // Store selected address in session for checkout process
        session(['checkout_address_id' => $this->selectedAddress]);

        // Flash success message
        $this->dispatch('notify', variant: 'success', message: 'Address selected for delivery.');

        return $this->redirectRoute('checkout.summary', navigate: true);
    }

    public function clearCheckoutAddress()
    {
        session()->forget('checkout_address_id');
    }
};
?>

<div>
    {{-- Breadcrumb --}}
    <x-slot:breadcrumbs>
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>


            <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>Checkout</flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Addresses</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </x-slot:breadcrumbs>

    <x-slot:heading>Customer Address</x-slot:heading>

    <div class="bg-white rounded-md border mb-4">
        <div class="px-3 py-2 border-b flex items-center justify-between gap-1">
            <div class="flex items-center gap-1">
                <flux:icon.check-circle variant="solid" @class([
                    'size-5',
                    'text-green-500' => auth()->user()->defaultAddress,
                    'text-zinc-500' => !auth()->user()->defaultAddress,
                ]) />
                <flux:heading level="3">Customer Address</flux:heading>
            </div>

            <flux:button size="xs" variant="ghost" icon="plus" :href="route('checkout.addresses.create')"
                wire:navigate class="text-xs! group">Add new
            </flux:button>
        </div>

        <div class="p-5">
            <form wire:submit="selectAddress">
                <flux:radio.group variant="cards" wire:model="selectedAddress"
                    class="grid! grid-cols-1! md:grid-cols-2! xl:grid-cols-3!">
                    @foreach ($this->addresses as $addressData)
                        <flux:radio value="{{ $addressData->id }}">
                            <flux:radio.indicator />
                            <div class="flex-1">
                                <flux:heading class="leading-4">{{ $addressData->full_name }}</flux:heading>

                                <div class="text-zinc-500 text-sm my-3 space-y-1">
                                    <flux:text>{{ $addressData->address }}</flux:text>

                                    <flux:text>
                                        {{ $addressData->area?->name . ', ' . $addressData->county?->name }}
                                    </flux:text>
                                    <flux:text>
                                        {{ implode(' / ', array_filter([$addressData->phone_number, $addressData->alternative_phone_number])) }}
                                    </flux:text>
                                </div>

                                @if ($addressData->is_default)
                                    <flux:badge size="sm">Default Address</flux:badge>
                                @endif
                            </div>

                            <flux:button :href="route('checkout.addresses.edit', $addressData->id)" wire:navigate
                                icon="pencil" size="xs" class="shrink-0 cursor-pointer z-20">
                            </flux:button>
                        </flux:radio>
                    @endforeach
                </flux:radio.group>

                <div class="flex items-center justify-end mt-5">
                    <flux:button type="submit" variant="primary" class="cursor-pointer">Select Address
                    </flux:button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white opacity-70 rounded-md border mb-4">
        <div class="px-3 py-2 flex items-center gap-1">
            <flux:icon.check-circle variant="solid" class="size-5 text-zinc-600" />
            <flux:heading level="3">Delivery Details</flux:heading>
        </div>
    </div>

    <flux:link :href="route('products')" wire:navigate class="text-xs">Go back & continue shopping
    </flux:link>
</div>
