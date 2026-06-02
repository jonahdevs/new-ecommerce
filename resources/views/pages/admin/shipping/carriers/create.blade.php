<?php

use App\Enums\CarrierDriver;
use App\Models\ShippingCarrier;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('New Carrier — Admin')] class extends Component {
    public string $name = '';
    public string $slug = '';
    public string $driver = '';
    public string $tracking_url_template = '';
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('shipping_carriers', 'slug')],
            'driver' => ['required', Rule::in(array_column(CarrierDriver::cases(), 'value'))],
            'tracking_url_template' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $carrier = ShippingCarrier::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'driver' => $this->driver,
            'tracking_url_template' => $this->tracking_url_template ?: null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ]);

        Flux::toast(heading: 'Carrier added', text: $this->name.' has been created. Configure its credentials next.', variant: 'success');
        $this->redirectRoute('admin.shipping.carriers.edit', $carrier, navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.shipping.carriers.index')" wire:navigate>Carriers</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>New carrier</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div>
        <flux:heading size="xl">New carrier</flux:heading>
        <flux:subheading>Add a third-party logistics provider. You can configure API credentials on the next step.</flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6">
        <flux:card class="max-w-xl space-y-4">
            <flux:input wire:model.live.debounce.400ms="name" label="Name" placeholder="e.g. Fargo Courier" required autofocus />
            <flux:input wire:model.blur="slug" label="Slug" description="Auto-generated from name." />

            <flux:select wire:model="driver" label="Driver" description="The integration type for this carrier.">
                <flux:select.option value="">Select a driver…</flux:select.option>
                @foreach (CarrierDriver::cases() as $d)
                    <flux:select.option :value="$d->value">{{ $d->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="tracking_url_template" label="Tracking URL template"
                placeholder="https://track.example.com/{number}"
                description="Use {number} as the placeholder for the tracking number." />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="sort_order" label="Sort order" type="number" min="0" />
                <div class="flex items-end pb-1">
                    <flux:switch wire:model="is_active" label="Active" />
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary" icon="arrow-right">
                    Create &amp; configure credentials
                </flux:button>
            </div>
        </flux:card>
    </form>
</div>
