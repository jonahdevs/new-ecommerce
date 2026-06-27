{{--
    Shared "choose or add a delivery address" modal.

    Backed by the InteractsWithAddressBook trait (state + persistence) and the
    addressMap() Alpine component (two-step map → details flow). The host page
    is responsible for the surrounding x-data="addressMap()" wrapper.
--}}
<flux:modal wire:model.self="showAddressModal" class="md:w-[640px] md:max-w-none" :dismissible="false">
    @if ($addressModalMode === 'select')
        <flux:heading class="uppercase tracking-wide">Choose a delivery address</flux:heading>
        <flux:subheading>Select where you'd like this order delivered.</flux:subheading>

        <div class="mt-5 space-y-3">
            @foreach ($this->addresses as $address)
                <button type="button" wire:key="modal-addr-{{ $address->id }}"
                    wire:click="selectAddress({{ $address->id }})"
                    class="block w-full rounded-md border p-4 text-left transition {{ $this->selectedAddressId === $address->id ? 'border-brand-500 ring-1 ring-brand-500' : 'border-zinc-200 hover:border-zinc-300' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-[10.5px] font-bold tracking-widest text-ink-3 uppercase">{{ $address->label }}</span>
                        @if ($address->is_default)
                            <span class="rounded-full bg-brand-500/10 px-2 py-0.5 text-[9.5px] font-bold tracking-wide text-brand-500 uppercase">Default</span>
                        @endif
                    </div>
                    <div class="mt-1 text-[13.5px] font-semibold text-ink">{{ $address->fullName() }}</div>
                    <div class="mt-1 text-[12.5px] leading-relaxed text-ink-2">{{ $address->oneLiner() }}</div>
                    @if ($address->phone)
                        <div class="mt-1 text-[12px] text-ink-3">{{ $address->phone }}</div>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="mt-5 flex items-center justify-between gap-3">
            <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">Cancel</flux:button>
            <flux:button type="button" variant="customer-outline" size="customer" icon="plus"
                wire:click="startAddressCreate">Add new address</flux:button>
        </div>
    @else
        <flux:heading class="uppercase tracking-wide">New address</flux:heading>
        <flux:subheading>
            <span x-show="step === 1">Pin where you'd like this order delivered.</span>
            <span x-show="step === 2" x-cloak>Now fill in the delivery address details.</span>
        </flux:subheading>

        <form wire:submit="saveAddress" class="mt-6">

            {{-- Step 1 — pin the location on the map --}}
            <div x-show="step === 1" class="space-y-3">
                @include('partials.storefront.address-map-pin')

                <div class="flex justify-end gap-3 pt-2">
                    @if ($this->addresses->isNotEmpty())
                        <flux:button type="button" variant="ghost" icon="chevron-left"
                            wire:click="$set('addressModalMode', 'select')">Back</flux:button>
                    @else
                        <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">Cancel</flux:button>
                    @endif
                    <flux:button type="button" variant="customer-primary" size="customer"
                        icon:trailing="chevron-right" x-on:click="showDetails()">Next</flux:button>
                </div>
            </div>

            {{-- Step 2 — address details --}}
            <div x-show="step === 2" x-cloak class="space-y-4">
                @include('partials.storefront.address-fields')

                <div class="flex justify-between gap-3 pt-2">
                    <flux:button type="button" variant="ghost" icon="chevron-left" x-on:click="showLocation()">Back</flux:button>
                    <flux:button type="submit" variant="customer-primary" size="customer">Add address</flux:button>
                </div>
            </div>
        </form>
    @endif
</flux:modal>
