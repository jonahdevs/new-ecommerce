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

    <div>
        <flux:heading size="xl">{{ $name }}</flux:heading>
        <flux:subheading>Update the warehouse address, contact details, and availability.</flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6">
        <flux:card class="max-w-2xl space-y-4">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model.live.debounce.400ms="name" label="Name" required class="sm:col-span-2" />
                <flux:input wire:model.blur="slug" label="Slug" class="sm:col-span-2" />
            </div>

            <flux:textarea wire:model="description" label="Description" rows="2" />

            <flux:separator text="Location" />

            <flux:input wire:model="address" label="Address" required />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="city" label="City" required />
                <flux:input wire:model="county" label="County" required />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="latitude" label="Latitude" placeholder="-1.2921"
                    description="Optional — for map display." />
                <flux:input wire:model="longitude" label="Longitude" placeholder="36.8219" />
            </div>

            <flux:separator text="Contact" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="phone" label="Phone" />
                <flux:input wire:model="email" label="Email" type="email" />
            </div>

            <flux:separator text="Settings" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="sort_order" label="Sort order" type="number" min="0" />
                <div class="flex items-end pb-1">
                    <flux:switch wire:model="is_active" label="Active" />
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </flux:card>
    </form>
</div>
