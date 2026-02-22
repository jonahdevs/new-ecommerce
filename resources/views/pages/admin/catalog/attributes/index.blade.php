<?php
use App\Models\Attribute;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

new #[Title('Product Attributes')] class extends Component {
    use WithPagination;

    public $search = '';

    public function delete($id)
    {
        $attribute = Attribute::findOrFail($id);

        // Logic check: You might want to prevent deletion if used by products later
        $attribute->delete();

        session()->flash('status', 'Attribute deleted successfully.');
    }

    public function with()
    {
        return [
            'attributes' => Attribute::query()->withCount('values')->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))->orderBy('sort_order')->paginate(15),
        ];
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Attributes</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Product Attributes</flux:heading>
            <flux:subheading>Manage options like Size, Color, and Material for your products</flux:subheading>
        </div>

        <flux:button href="{{ route('admin.attributes.create') }}" variant="primary" icon="plus" wire:navigate>
            Create Attribute
        </flux:button>
    </div>


    <div class="mb-4 mt-6">
        <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Search attributes (e.g. Color)..."
            class="max-w-md" />
    </div>

    <flux:card class="p-0">
        <flux:table :paginate="$attributes">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Name</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Values Count</flux:table.column>
                <flux:table.column>Usage</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($attributes as $attribute)
                    <flux:table.row :key="$attribute->id">
                        <flux:table.cell class="ps-4!">
                            <div class="font-medium text-zinc-800 dark:text-white">{{ $attribute->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $attribute->slug }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc" variant="outline" class="capitalize">
                                {{ $attribute->type }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text size="sm">{{ $attribute->values_count }} values</flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex gap-2">
                                @if ($attribute->used_for_variations)
                                    <flux:badge size="sm" color="indigo" variant="subtle">Variations</flux:badge>
                                @endif
                                @if ($attribute->is_visible)
                                    <flux:badge size="sm" color="blue" variant="subtle">Visible</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" :color="$attribute->is_active ? 'green' : 'red'" variant="flat">
                                {{ $attribute->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                href="{{ route('admin.attributes.edit', $attribute) }}" wire:navigate />

                            <flux:button variant="ghost" size="sm" icon="trash" class="text-red-500!"
                                icon-variant="outline"
                                wire:confirm="Are you sure? This will remove the attribute and all its values."
                                wire:click="delete({{ $attribute->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
