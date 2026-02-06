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


            <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>Checkout</flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Addresses</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>


    <div class="mx-auto container px-4 py-4 min-h-[80svh]">
        <!-- Checkout Summary Header -->
        <flux:heading level="1" class="text-2xl! font-bold! mb-3">Customer Address</flux:heading>

        <div class="grid grid-cols-4 gap-6">
            <div class="col-span-3 space-y-3">
                <div class="bg-white rounded-sm border">
                    <div class="px-3 py-2 border-b flex items-center justify-between gap-1">
                        <div class="flex items-center gap-1">
                            <flux:icon.check-circle variant="solid" @class([
                                'size-5',
                                'text-green-500' => auth()->user()->defaultAddress,
                                'text-zinc-500' => !auth()->user()->defaultAddress,
                            ]) />
                            <flux:heading level="3">Customer Address</flux:heading>
                        </div>

                        <flux:button size="xs" variant="ghost" icon="plus"
                            :href="route('checkout.addresses.create')" wire:navigate class="text-sm! group">Add new
                        </flux:button>
                    </div>

                    <div class="p-5">
                        <div class="space-y-2">
                            @foreach ($this->addresses as $addressData)
                                <div class="border rounded-md flex flex-col">
                                    <div class="p-3 flex items-start">
                                        <div class="flex-1">
                                            <flux:heading>{{ $addressData->full_name }}</flux:heading>

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

                                        <flux:button :href="route('checkout.addresses.edit', $addressData->id)"
                                            wire:navigate icon="pencil" size="xs" class="shrink-0"></flux:button>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-white opacity-70 rounded-sm border">
                    <div class="px-3 py-2 flex items-center gap-1">
                        <flux:icon.check-circle variant="solid" class="size-5 text-zinc-600" />
                        <flux:heading level="3">Delivery Details</flux:heading>
                    </div>
                </div>

                <flux:link :href="route('products')" wire:navigate class="text-xs">Go back & continue shopping
                </flux:link>
            </div>

            <div class="col-span-1">
                <livewire:order-summary />
            </div>
        </div>
    </div>
</div>
