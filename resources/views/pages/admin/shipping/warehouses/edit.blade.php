<?php

use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Edit Warehouse — Admin')] class extends Component {
    #[Locked]
    public Warehouse $warehouse;

    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $address = '';
    public string $city = '';
    public string $county = '';
    public string $latitude = '';
    public string $longitude = '';
    public string $phone = '';
    public string $email = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    private bool $slugManuallyEdited = false;

    public function mount(Warehouse $warehouse): void
    {
        $this->warehouse = $warehouse;
        $this->name = $warehouse->name;
        $this->slug = $warehouse->slug;
        $this->description = (string) $warehouse->description;
        $this->address = $warehouse->address;
        $this->city = $warehouse->city;
        $this->county = $warehouse->county;
        $this->latitude = $warehouse->latitude !== null ? (string) $warehouse->latitude : '';
        $this->longitude = $warehouse->longitude !== null ? (string) $warehouse->longitude : '';
        $this->phone = (string) $warehouse->phone;
        $this->email = (string) $warehouse->email;
        $this->is_active = (bool) $warehouse->is_active;
        $this->sort_order = (int) $warehouse->sort_order;
        $this->slugManuallyEdited = true;
    }

    public function updatedName(): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
        $this->slug = Str::slug($this->slug);
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('warehouses', 'slug')->ignore($this->warehouse->id)],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'county' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $this->warehouse->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'address' => $this->address,
            'city' => $this->city,
            'county' => $this->county,
            'latitude' => $this->latitude !== '' ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== '' ? (float) $this->longitude : null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ]);

        Flux::toast(heading: 'Warehouse saved', text: $this->name.' has been updated.', variant: 'success');
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.shipping.warehouses.index')" wire:navigate>Warehouses</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <form wire:submit="save">
        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $name }}</flux:heading>
                <flux:subheading>Update the warehouse address, contact details, and availability.</flux:subheading>
            </div>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('admin.shipping.warehouses.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Save changes</flux:button>
            </div>
        </div>

        {{-- Two-column layout --}}
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main column --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Details --}}
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="base" class="uppercase tracking-wide">Warehouse details</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                        <flux:input wire:model.live.debounce.400ms="name" label="Name" required />
                        <flux:input wire:model.blur="slug" label="Slug"
                            description="Auto-generated from name. Used in URLs." />
                        <flux:textarea wire:model="description" label="Description" rows="2" />
                    </div>
                </flux:card>

                {{-- Location --}}
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="base" class="uppercase tracking-wide">Location</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                        <flux:input wire:model="address" label="Address" required />
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:input wire:model="city" label="City" required />
                            <flux:input wire:model="county" label="County" required />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:input wire:model="latitude" label="Latitude" placeholder="-1.2921"
                                description="Optional — for map display." />
                            <flux:input wire:model="longitude" label="Longitude" placeholder="36.8219" />
                        </div>
                    </div>
                </flux:card>

                {{-- Contact --}}
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="base" class="uppercase tracking-wide">Contact</flux:heading>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
                        <flux:input wire:model="phone" label="Phone" />
                        <flux:input wire:model="email" label="Email" type="email" />
                    </div>
                </flux:card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <flux:card class="overflow-hidden p-0">
                    <div class="border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
                        <flux:heading size="sm" class="uppercase tracking-wide">Settings</flux:heading>
                    </div>
                    <div class="space-y-4 p-6">
                        <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                            <flux:label>Active</flux:label>
                            <flux:switch wire:model="is_active" />
                        </div>
                        <flux:input wire:model="sort_order" label="Sort order" type="number" min="0"
                            description="Lower = shown first at checkout." />
                    </div>
                </flux:card>
            </div>
        </div>
    </form>
</div>
