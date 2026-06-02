<?php

use App\Enums\ShippingMethodType;
use App\Models\ShippingMethod;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Edit Shipping Method — Admin')] class extends Component {
    #[Locked]
    public ShippingMethod $shippingMethod;

    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $type = 'delivery';
    public bool $is_active = true;
    public int $sort_order = 0;

    private bool $slugManuallyEdited = false;

    public function mount(ShippingMethod $shippingMethod): void
    {
        $this->shippingMethod = $shippingMethod;
        $this->name = $shippingMethod->name;
        $this->slug = $shippingMethod->slug;
        $this->description = (string) $shippingMethod->description;
        $this->type = $shippingMethod->type->value;
        $this->is_active = (bool) $shippingMethod->is_active;
        $this->sort_order = (int) $shippingMethod->sort_order;
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('shipping_methods', 'slug')->ignore($this->shippingMethod->id)],
            'type' => ['required', Rule::in(array_column(ShippingMethodType::cases(), 'value'))],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $this->shippingMethod->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ]);

        Flux::toast(heading: 'Method saved', text: $this->name.' has been updated.', variant: 'success');
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.shipping.methods.index')" wire:navigate>Shipping methods</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div>
        <flux:heading size="xl">{{ $name }}</flux:heading>
        <flux:subheading>Edit the customer-facing label. Pricing is managed on the carrier that offers this method.</flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6">
        <flux:card class="max-w-xl space-y-4">
            <flux:input wire:model.live.debounce.400ms="name" label="Name" required />
            <flux:input wire:model.blur="slug" label="Slug" description="Auto-generated from name." />
            <flux:textarea wire:model="description" label="Description" rows="2" />

            <flux:select wire:model="type" label="Type">
                @foreach (ShippingMethodType::cases() as $t)
                    <flux:select.option :value="$t->value">{{ $t->label() }}</flux:select.option>
                @endforeach
            </flux:select>

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
