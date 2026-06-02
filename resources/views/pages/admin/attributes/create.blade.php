<?php

use App\Enums\AttributeType;
use App\Models\Attribute;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('New Attribute — Admin')] class extends Component {
    public string $name = '';
    public string $slug = '';
    public string $type = 'select';
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('attributes', 'slug')],
            'type' => ['required', Rule::in(array_column(AttributeType::cases(), 'value'))],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $attribute = Attribute::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ]);

        Flux::toast(heading: 'Attribute created', text: $this->name.' has been added. Now add its values.', variant: 'success');
        $this->redirectRoute('admin.attributes.edit', $attribute, navigate: true);
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.attributes.index')" wire:navigate>Attributes</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>New attribute</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div>
        <flux:heading size="xl">New attribute</flux:heading>
        <flux:subheading>Create a product attribute such as Colour, Material or Size.</flux:subheading>
    </div>

    <form wire:submit="save" class="mt-6">
        <flux:card class="space-y-4 max-w-xl">
            <flux:input wire:model.live.debounce.400ms="name" label="Name" placeholder="e.g. Material" required autofocus />
            <flux:input wire:model.blur="slug" label="Slug" description="Auto-generated from name." placeholder="material" />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="type" label="Type">
                    @foreach (AttributeType::cases() as $t)
                        <flux:select.option :value="$t->value" class="capitalize">{{ ucfirst($t->value) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="sort_order" label="Sort order" type="number" min="0" />
            </div>

            <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                <flux:label>Active</flux:label>
                <flux:switch wire:model="is_active" />
            </div>

            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary" icon="arrow-right">
                    Create &amp; add values
                </flux:button>
            </div>
        </flux:card>
    </form>
</div>
