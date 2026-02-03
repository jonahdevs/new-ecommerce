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
    }

    #[Computed]
    public function addresses()
    {
        return auth()->user()->addresses;
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

            <flux:breadcrumbs.item>Addresses</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>


    <div class="mx-auto container px-4 py-4 min-h-[80svh]">
        <!-- Checkout Summary Header -->
        <flux:heading level="1" class="text-2xl! font-bold! mb-3">Customer Addresses</flux:heading>

        <div class="grid grid-cols-4 gap-6">
            <div class="col-span-3 bg-white rounded-sm border">
                <div class="px-3 py-2 border-b">
                    <flux:heading level="3">Customer Addresses</flux:heading>
                </div>

                <div class="p-5">
                    <div class="space-y-2">
                        @foreach ($this->addresses as $addressData)
                            <div class="border rounded-md flex flex-col">
                                <div class="p-3 flex items-start">
                                    <div class="flex-1">
                                        <p>{{ $addressData->first_name . ' ' . $addressData->last_name }}</p>

                                        <div class="text-zinc-500 text-sm my-3 space-y-1">
                                            <p>{{ $addressData->address }}</p>

                                            <p>{{ $addressData->area->name . ', ' . $addressData->county->name }}</p>
                                            <p>{{ implode(' / ', array_filter([$addressData->phone_number, $addressData->alternative_phone_number])) }}
                                            </p>
                                        </div>

                                        @if ($addressData->is_default)
                                            <flux:badge size="sm">Default Address</flux:badge>
                                        @endif
                                    </div>

                                    <flux:button :href="route('checkout.addresses.edit', $addressData->id)"
                                        wire:navigate icon="pencil" size="xs" class="shrink-0"></flux:button>
                                </div>

                            </div>
                        @endforeach
                    </div>
                </div>
            </div>


            <div class="col-span-1">
                <livewire:order-summary />
            </div>
        </div>
    </div>
</div>
