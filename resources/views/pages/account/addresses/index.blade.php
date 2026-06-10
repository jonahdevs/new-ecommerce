<?php

use App\Models\DeliveryZone;
use App\Services\DeliveryResolver;
use Artesaos\SEOTools\Facades\SEOMeta;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::account')] #[Title('Addresses')] class extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $label = 'Home';

    public string $name = '';

    public string $phone = '';

    public string $alternative_phone = '';

    public string $line1 = '';

    public string $delivery_instructions = '';

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
            'label' => ['required', 'string', \Illuminate\Validation\Rule::in(['Home', 'Work', 'Other'])],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'alternative_phone' => ['nullable', 'string', 'max:30'],
            'line1' => ['required', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:500'],
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
        $this->name = $address->name;
        $this->phone = $address->phone ?? '';
        $this->alternative_phone = $address->alternative_phone ?? '';
        $this->line1 = $address->line1;
        $this->delivery_instructions = $address->delivery_instructions ?? '';
        $this->is_default = $address->is_default;
        $this->latitude = $address->latitude;
        $this->longitude = $address->longitude;
        $this->showModal = true;
    }

    #[Computed]
    public function pinnedZone(): ?DeliveryZone
    {
        return app(DeliveryResolver::class)->resolveZone($this->latitude, $this->longitude);
    }

    public function save(): void
    {
        $data = $this->validate();

        $data['delivery_zone_id'] = app(DeliveryResolver::class)
            ->resolveZone($data['latitude'] ?? null, $data['longitude'] ?? null)?->id;

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
        $this->name = '';
        $this->phone = '';
        $this->alternative_phone = '';
        $this->line1 = '';
        $this->delivery_instructions = '';
        $this->is_default = false;
        $this->latitude = null;
        $this->longitude = null;
        $this->resetValidation();
    }
}; ?>

@include('partials.storefront.address-map-scripts')

<div class="page-fade" x-data="addressMap()" x-effect="$wire.showModal ? open() : close()">

    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Addresses</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

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
                    <flux:card wire:key="addr-{{ $address->id }}"
                               class="flex h-full flex-col p-0 {{ $address->is_default ? 'border-brand-500 ring-1 ring-brand-500' : '' }}">

                        {{-- Label + default badge --}}
                        <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-5 py-3">
                            <flux:heading size="sm" class="uppercase tracking-wide">{{ $address->label }}</flux:heading>
                            @if ($address->is_default)
                                <flux:badge color="lime" size="sm">Default</flux:badge>
                            @endif
                        </div>

                        {{-- Details — grows so every card's footer sits on the same baseline --}}
                        <div class="flex-1 px-5 py-4">
                            <div class="font-semibold text-ink">{{ $address->fullName() }}</div>
                            <div class="mt-2 space-y-0.5 text-[13px] leading-relaxed text-ink-2">
                                <div>{{ $address->line1 }}</div>
                            </div>
                            @if ($address->phone)
                                <div class="mt-2 text-[12.5px] text-ink-3">{{ $address->phone }}</div>
                            @endif
                            @if ($address->hasCoordinates())
                                <a href="https://www.google.com/maps?q={{ $address->latitude }},{{ $address->longitude }}"
                                   target="_blank"
                                   class="mt-3 inline-flex items-center gap-1.5 text-[12px] font-semibold text-brand-500 hover:text-brand-600">
                                    <flux:icon.map-pin variant="micro" class="size-3.5" />
                                    View on map
                                </a>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-1 border-t border-zinc-100 px-3 py-2">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="openEdit({{ $address->id }})">Edit</flux:button>
                            @if (! $address->is_default)
                                <flux:button variant="ghost" size="sm" wire:click="setDefault({{ $address->id }})">Set as default</flux:button>
                                <flux:spacer />
                                <flux:button variant="ghost" size="sm" icon="trash-2"
                                             wire:click="delete({{ $address->id }})"
                                             wire:confirm="Delete this address?"
                                             class="text-red-500! hover:text-red-600!" />
                            @endif
                        </div>
                    </flux:card>
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
                    <flux:button type="button" variant="ghost" x-on:click="$flux.modals().close()">Cancel</flux:button>
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
