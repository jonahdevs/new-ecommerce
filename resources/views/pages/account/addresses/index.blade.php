<?php

use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('Addresses — Sheffield')] class extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $label = 'Home';

    public string $first_name = '';

    public string $last_name = '';

    public string $phone = '';

    public string $line1 = '';

    public string $line2 = '';

    public string $city = 'Nairobi';

    public string $postal_code = '';

    public string $country = 'KE';

    public bool $is_default = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public function mount(): void
    {
        SEOMeta::setRobots('noindex,follow');
    }

    #[Computed]
    public function addresses()
    {
        return auth()->user()->addresses()->orderByDesc('is_default')->orderBy('created_at')->get();
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'is_default' => ['boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $address = auth()->user()->addresses()->findOrFail($id);
        $this->editingId = $id;
        $this->label = $address->label;
        $this->first_name = $address->first_name;
        $this->last_name = $address->last_name;
        $this->phone = $address->phone ?? '';
        $this->line1 = $address->line1;
        $this->line2 = $address->line2 ?? '';
        $this->city = $address->city;
        $this->postal_code = $address->postal_code ?? '';
        $this->country = $address->country;
        $this->is_default = $address->is_default;
        $this->latitude = $address->latitude;
        $this->longitude = $address->longitude;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($data['is_default']) {
            auth()->user()->addresses()->update(['is_default' => false]);
        }

        if ($this->editingId) {
            auth()->user()->addresses()->findOrFail($this->editingId)->update($data);
            Flux::toast(heading: 'Address updated', text: 'Your address has been saved.');
        } else {
            if (auth()->user()->addresses()->count() === 0) {
                $data['is_default'] = true;
            }
            auth()->user()->addresses()->create($data);
            Flux::toast(heading: 'Address added', text: 'Your new address has been saved.');
        }

        $this->showModal = false;
        unset($this->addresses);
    }

    public function setDefault(int $id): void
    {
        auth()->user()->addresses()->update(['is_default' => false]);
        auth()->user()->addresses()->findOrFail($id)->update(['is_default' => true]);
        unset($this->addresses);
    }

    public function delete(int $id): void
    {
        auth()->user()->addresses()->findOrFail($id)->delete();
        unset($this->addresses);
        Flux::toast(heading: 'Address removed', text: 'The address has been deleted.', variant: 'warning');
    }

    private function resetForm(): void
    {
        $this->label = 'Home';
        $this->first_name = '';
        $this->last_name = '';
        $this->phone = '';
        $this->line1 = '';
        $this->line2 = '';
        $this->city = 'Nairobi';
        $this->postal_code = '';
        $this->country = 'KE';
        $this->is_default = false;
        $this->latitude = null;
        $this->longitude = null;
        $this->resetValidation();
    }
}; ?>

@include('partials.storefront.address-map-scripts')

<div class="page-fade" x-data="addressMap()" x-effect="$wire.showModal ? open() : close()">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Addresses</flux:heading>
            <flux:text class="mt-1">Manage your saved delivery addresses.</flux:text>
        </div>
        <flux:button variant="customer-primary" size="customer" wire:click="openCreate" icon="plus">
            Add address
        </flux:button>
    </div>

    {{-- Address cards --}}
    <div class="mt-6">
        @if ($this->addresses->isEmpty())
            <flux:card class="py-14 text-center">
                <flux:icon.map-pin variant="outline" class="mx-auto size-9 text-ink-4" />
                <flux:heading size="sm" class="mt-4">No addresses saved</flux:heading>
                <flux:text class="mt-1">Add a delivery address to speed up checkout.</flux:text>
                <flux:button variant="customer-primary" size="customer" wire:click="openCreate" class="mt-5">
                    Add address
                </flux:button>
            </flux:card>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($this->addresses as $address)
                    <div wire:key="addr-{{ $address->id }}"
                         class="relative rounded-md border bg-white p-5 {{ $address->is_default ? 'border-brand-500' : 'border-zinc-200' }}">

                        @if ($address->is_default)
                            <div class="absolute right-4 top-4">
                                <span class="rounded-full bg-brand-500/10 px-2.5 py-0.5 text-[10.5px] font-bold tracking-wide text-brand-500 uppercase">
                                    Default
                                </span>
                            </div>
                        @endif

                        <div class="text-[10.5px] font-bold tracking-[0.1em] text-ink-3 uppercase">{{ $address->label }}</div>
                        <div class="mt-1 font-semibold text-ink">{{ $address->fullName() }}</div>

                        <div class="mt-2 space-y-0.5 text-[13px] leading-relaxed text-ink-2">
                            <div>{{ $address->line1 }}</div>
                            @if ($address->line2)
                                <div>{{ $address->line2 }}</div>
                            @endif
                            <div>{{ $address->city }}{{ $address->postal_code ? ', ' . $address->postal_code : '' }}</div>
                            @if ($address->phone)
                                <flux:text size="sm" class="mt-1 text-ink-3">{{ $address->phone }}</flux:text>
                            @endif
                        </div>

                        {{-- Pin indicator --}}
                        @if ($address->hasCoordinates())
                            <a href="https://www.google.com/maps?q={{ $address->latitude }},{{ $address->longitude }}"
                               target="_blank"
                               class="mt-3 inline-flex items-center gap-1.5 text-[12px] font-semibold text-brand-500 hover:text-brand-600">
                                <flux:icon.map-pin variant="micro" class="size-3.5" />
                                View on map
                            </a>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <flux:button variant="customer-outline" size="customer" wire:click="openEdit({{ $address->id }})">
                                Edit
                            </flux:button>
                            @if (!$address->is_default)
                                <flux:button variant="ghost" size="xs" wire:click="setDefault({{ $address->id }})">
                                    Set as default
                                </flux:button>
                                <flux:button variant="ghost" size="xs"
                                             wire:click="delete({{ $address->id }})"
                                             wire:confirm="Delete this address?"
                                             class="text-red-500! hover:text-red-600!">
                                    Delete
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Create / Edit modal --}}
    <flux:modal wire:model.self="showModal" class="md:w-[560px]" :dismissible="false">
        <flux:heading>{{ $editingId ? 'Edit address' : 'New address' }}</flux:heading>
        <flux:subheading>
            <span x-show="step === 1">{{ $editingId ? 'Update where this address is located.' : 'Pin where you’d like your deliveries to arrive.' }}</span>
            <span x-show="step === 2" x-cloak>{{ $editingId ? 'Update your delivery address details.' : 'Now fill in the delivery address details.' }}</span>
        </flux:subheading>

        <form wire:submit="save" class="mt-6">

            {{-- Step 1 — pin the location on the map --}}
            <div x-show="step === 1" class="space-y-3">
                @include('partials.storefront.address-map-pin')

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button type="button" variant="ghost" x-on:click="$flux.close()">Cancel</flux:button>
                    <flux:button type="button" variant="customer-primary" size="customer" icon:trailing="arrow-right" x-on:click="showDetails()">Next</flux:button>
                </div>
            </div>

            {{-- Step 2 — address details --}}
            <div x-show="step === 2" x-cloak class="space-y-4">
                @include('partials.storefront.address-fields')

                <div class="flex justify-between gap-3 pt-2">
                    <flux:button type="button" variant="ghost" icon="arrow-left" x-on:click="showLocation()">Back</flux:button>
                    <flux:button type="submit" variant="customer-primary" size="customer">
                        {{ $editingId ? 'Save changes' : 'Add address' }}
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

</div>
