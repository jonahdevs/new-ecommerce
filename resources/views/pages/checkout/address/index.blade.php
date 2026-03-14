<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, Computed};

new #[Layout('layouts.checkout')] class extends Component {
    public ?int $selectedAddress = null;
    public bool $autoSelected = false;

    public function mount()
    {
        $user = auth()->user();

        if ($user->addresses()->doesntExist()) {
            return $this->redirectRoute('checkout.addresses.create', navigate: true);
        }

        $addresses = $user->addresses()->orderByDesc('is_default')->oldest()->get();

        // Only one address — auto-select and skip this page
        if ($addresses->count() === 1) {
            $address = $addresses->first();
            $this->autoSelected = true;
        }

        // Multiple addresses — resolve which one to pre-select
        $sessionAddress = session('checkout_address_id');

        $this->selectedAddress = $sessionAddress && $user->addresses()->where('id', $sessionAddress)->exists() ? $sessionAddress : $user->defaultAddress?->id ?? $addresses->first()->id;
    }
    #[Computed]
    public function addresses()
    {
        return auth()->user()->addresses()->orderByDesc('is_default')->oldest()->get();
    }

    public function selectAddress(): void
    {
        $user = auth()->user();

        if (!$user->addresses()->where('id', $this->selectedAddress)->exists()) {
            $this->addError('selectedAddress', 'Invalid address selected.');
            return;
        }

        session(['checkout_address_id' => $this->selectedAddress]);

        $this->dispatch('notify', variant: 'success', message: 'Address selected for delivery.');

        $this->redirectRoute('checkout.summary', navigate: true);
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

    <flux:card class="p-0 mb-4">
        <div class="px-3 py-2 border-b flex items-center justify-between gap-1">
            <div class="flex items-center gap-1">
                <flux:icon.check-circle variant="solid" @class([
                    'size-5',
                    'text-green-500' => auth()->user()->defaultAddress,
                    'text-zinc-400' => !auth()->user()->defaultAddress,
                ]) />
                <flux:heading level="3">Select Delivery Address</flux:heading>
            </div>

            <flux:button size="xs" variant="ghost" icon="plus" :href="route('checkout.addresses.create')"
                wire:navigate>
                Add new
            </flux:button>
        </div>

        <div class="p-5">
            @error('selectedAddress')
                <div
                    class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 rounded-md px-3 py-2 text-sm text-red-700">
                    <flux:icon.exclamation-circle class="size-4 shrink-0" />
                    <span>{{ $message }}</span>
                </div>
            @enderror

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                @foreach ($this->addresses as $address)
                    @php $isSelected = $selectedAddress === $address->id; @endphp

                    <div wire:key="address-{{ $address->id }}"
                        wire:click="$set('selectedAddress', {{ $address->id }})" @class([
                            'border rounded-lg flex flex-col overflow-hidden cursor-pointer transition-all',
                            'border-zinc-800 ring-1 ring-zinc-800' => $isSelected,
                            'border-zinc-200 hover:border-zinc-400' => !$isSelected,
                        ])>

                        {{-- Card Body --}}
                        <div @class(['p-4 flex-1', 'bg-zinc-50' => $address->is_default])>
                            <div class="flex items-start justify-between gap-2">
                                <flux:heading class="leading-4">{{ $address->full_name }}</flux:heading>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    @if ($address->is_default)
                                        <flux:badge color="green" size="sm">Default</flux:badge>
                                    @endif
                                </div>
                            </div>

                            <div class="my-3 space-y-1">
                                <flux:text>{{ $address->address }}</flux:text>
                                <flux:text>
                                    {{ implode(', ', array_filter([$address->area?->name, $address->county?->name])) }}
                                </flux:text>
                                <flux:text>
                                    {{ implode(' / ', array_filter([$address->phone_number, $address->alternative_phone_number])) }}
                                </flux:text>
                            </div>
                        </div>

                        {{-- Card Footer --}}
                        <div class="px-4 py-2 border-t flex items-center justify-end">
                            <flux:button icon="pencil" size="xs" variant="ghost" class="cursor-pointer"
                                :href="route('checkout.addresses.edit', $address->id)" wire:navigate wire:click.stop>
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Confirm Button --}}
            @if (!$autoSelected)
                <div class="flex items-center justify-end gap-3 border-t pt-4">
                    <flux:button variant="ghost" :href="route('checkout.summary')" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button variant="primary" wire:click="selectAddress" :disabled="!$selectedAddress"
                        class="cursor-pointer">
                        Confirm Address
                    </flux:button>
                </div>
            @endif
        </div>
    </flux:card>

    <flux:card class="opacity-70 p-0 mb-4">
        <div class="px-3 py-2 flex items-center gap-1">
            <flux:icon.check-circle variant="solid" class="size-5 text-zinc-400" />
            <flux:heading level="3">Delivery Details</flux:heading>
        </div>
    </flux:card>

    <flux:card class="opacity-70 p-0 mb-4">
        <div class="px-3 py-2 flex items-center gap-1">
            <flux:icon.check-circle variant="solid" class="size-5 text-zinc-400" />
            <flux:heading level="3">Payment Methods</flux:heading>
        </div>
    </flux:card>

    <flux:link :href="route('shop.index')" wire:navigate class="text-xs">
        Go back & continue shopping
    </flux:link>
</div>
