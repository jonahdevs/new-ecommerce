<?php

use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('New Warehouse — Admin')] class extends Component {
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $address = '';
    public string $city = 'Nairobi';
    public string $county = 'Nairobi';
    public string $latitude = '';
    public string $longitude = '';
    public string $phone = '';
    public string $email = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    private bool $slugManuallyEdited = false;

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
            'slug' => ['required', 'string', 'max:255', Rule::unique('warehouses', 'slug')],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'county' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $warehouse = Warehouse::create([
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

        Flux::toast(heading: 'Warehouse created', text: $this->name.' is now available for order pickup.', variant: 'success');
        $this->redirectRoute('admin.shipping.warehouses.edit', $warehouse, navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.shipping.warehouses.index')" wire:navigate>Warehouses</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>New warehouse</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div>
        <flux:heading size="xl">New warehouse</flux:heading>
        <flux:subheading>A physical stock location customers can collect orders from.</flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6">
        <flux:card class="max-w-2xl space-y-4">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model.live.debounce.400ms="name" label="Name"
                    placeholder="e.g. Sheffield Africa Logistics" required autofocus class="sm:col-span-2" />
                <flux:input wire:model.blur="slug" label="Slug" description="Auto-generated from name." class="sm:col-span-2" />
            </div>

            <flux:textarea wire:model="description" label="Description" rows="2"
                placeholder="Internal notes about this location…" />

            <flux:separator text="Location" />

            <flux:input wire:model="address" label="Address" placeholder="Off Old Mombasa Road, Industrial Area" required />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="city" label="City" placeholder="Nairobi" required />
                <flux:input wire:model="county" label="County" placeholder="Nairobi" required />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="latitude" label="Latitude" placeholder="-1.2921"
                    description="Optional — for map display." />
                <flux:input wire:model="longitude" label="Longitude" placeholder="36.8219" />
            </div>

            <flux:separator text="Contact" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="phone" label="Phone" placeholder="+254 700 000 000" />
                <flux:input wire:model="email" label="Email" type="email" placeholder="warehouse@store.com" />
            </div>

            <flux:separator text="Settings" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="sort_order" label="Sort order" type="number" min="0"
                    description="Lower = shown first at checkout." />
                <div class="flex items-end pb-1">
                    <flux:switch wire:model="is_active" label="Active" />
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary">Create warehouse</flux:button>
            </div>
        </flux:card>
    </form>
</div>
