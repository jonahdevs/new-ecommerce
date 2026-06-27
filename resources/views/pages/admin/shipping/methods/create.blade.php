<?php

use App\Enums\ShippingMethodType;
use App\Models\ShippingMethod;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('New Shipping Method | Admin')] class extends Component {
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $type = 'delivery';
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('shipping_methods', 'slug')],
            'type' => ['required', Rule::in(array_column(ShippingMethodType::cases(), 'value'))],
            'sort_order' => ['integer', 'min:0'],
        ]);

        ShippingMethod::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ]);

        Flux::toast(heading: 'Method created', text: $this->name.' is now available.', variant: 'success');
        $this->redirectRoute('admin.shipping.methods.index', navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.shipping.methods.index')" wire:navigate>Shipping methods</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>New method</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div>
        <flux:heading size="xl">New shipping method</flux:heading>
        <flux:subheading>Define a customer-facing delivery label. Pricing is configured on each carrier.</flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6">
        <flux:card class="max-w-xl space-y-4">
            <flux:input wire:model.live.debounce.400ms="name" label="Name"
                placeholder="e.g. Standard Delivery" required autofocus />
            <flux:input wire:model.blur="slug" label="Slug" description="Auto-generated from name." />
            <flux:textarea wire:model="description" label="Description" rows="2"
                placeholder="Short description shown to customers at checkout…" />

            <flux:select wire:model="type" label="Type">
                @foreach (ShippingMethodType::cases() as $t)
                    <flux:select.option :value="$t->value">{{ $t->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="sort_order" label="Sort order" type="number" min="0"
                    description="Lower = shown first at checkout." />
                <div class="flex items-end pb-1">
                    <flux:switch wire:model="is_active" label="Active" />
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary">Create method</flux:button>
            </div>
        </flux:card>
    </form>
</div>
