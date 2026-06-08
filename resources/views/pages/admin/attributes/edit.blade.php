<?php

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Edit Attribute — Admin')] class extends Component
{
    #[Locked]
    public Attribute $attribute;

    public string $name = '';

    public string $slug = '';

    public string $type = 'select';

    public bool $is_active = true;

    public int $sort_order = 0;

    public string $valueLabel = '';

    public string $valueValue = '';

    public string $valueColorCode = '';

    public int $valueSortOrder = 0;

    public bool $showAddValueModal = false;

    public bool $showEditValueModal = false;

    public ?int $editingValueId = null;

    public string $editValueLabel = '';

    public string $editValueValue = '';

    public string $editValueColorCode = '';

    public int $editValueSortOrder = 0;

    private bool $slugManuallyEdited = false;

    public function mount(Attribute $attribute): void
    {
        $this->attribute = $attribute;
        $this->name = $attribute->name;
        $this->slug = $attribute->slug;
        $this->type = $attribute->type->value;
        $this->is_active = (bool) $attribute->is_active;
        $this->sort_order = (int) $attribute->sort_order;
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('attributes', 'slug')->ignore($this->attribute->id)],
            'type' => ['required', Rule::in(array_column(AttributeType::cases(), 'value'))],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $this->attribute->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ]);

        Flux::toast(heading: 'Attribute saved', text: $this->name.' has been updated.', variant: 'success');
    }

    #[Computed]
    public function values()
    {
        return $this->attribute->values()->orderBy('sort_order')->orderBy('label')->get();
    }

    public function addValue(): void
    {
        $this->validate([
            'valueLabel' => ['required', 'string', 'max:100'],
            'valueValue' => ['required', 'string', 'max:100'],
            'valueColorCode' => ['nullable', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'valueSortOrder' => ['integer', 'min:0'],
        ]);

        AttributeValue::create([
            'attribute_id' => $this->attribute->id,
            'label' => $this->valueLabel,
            'value' => $this->valueValue,
            'slug' => Str::slug($this->valueValue),
            'color_code' => $this->valueColorCode ?: null,
            'sort_order' => $this->valueSortOrder,
            'is_active' => true,
        ]);

        $this->reset(['valueLabel', 'valueValue', 'valueColorCode']);
        $this->valueSortOrder = 0;
        $this->resetValidation(['valueLabel', 'valueValue', 'valueColorCode', 'valueSortOrder']);
        $this->showAddValueModal = false;
        unset($this->values);
        Flux::toast(heading: 'Value added', text: $this->valueLabel.' has been added.', variant: 'success');
    }

    public function openEditValue(int $valueId): void
    {
        $value = AttributeValue::findOrFail($valueId);
        $this->editingValueId = $valueId;
        $this->editValueLabel = $value->label;
        $this->editValueValue = $value->value;
        $this->editValueColorCode = (string) $value->color_code;
        $this->editValueSortOrder = (int) $value->sort_order;
        $this->resetValidation(['editValueLabel', 'editValueValue', 'editValueColorCode', 'editValueSortOrder']);
        $this->showEditValueModal = true;
    }

    public function saveValue(): void
    {
        $this->validate([
            'editValueLabel' => ['required', 'string', 'max:100'],
            'editValueValue' => ['required', 'string', 'max:100'],
            'editValueColorCode' => ['nullable', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'editValueSortOrder' => ['integer', 'min:0'],
        ]);

        AttributeValue::findOrFail($this->editingValueId)->update([
            'label' => $this->editValueLabel,
            'value' => $this->editValueValue,
            'slug' => Str::slug($this->editValueValue),
            'color_code' => $this->editValueColorCode ?: null,
            'sort_order' => $this->editValueSortOrder,
        ]);

        $this->showEditValueModal = false;
        unset($this->values);
        Flux::toast(heading: 'Value updated', text: $this->editValueLabel.' has been saved.', variant: 'success');
    }

    public function toggleValueActive(int $valueId): void
    {
        $value = AttributeValue::findOrFail($valueId);
        $value->update(['is_active' => ! $value->is_active]);
        unset($this->values);
    }

    public function deleteValue(int $valueId): void
    {
        AttributeValue::findOrFail($valueId)->delete();
        unset($this->values);
        Flux::toast(heading: 'Value deleted', text: 'The value has been removed.', variant: 'success');
    }
}; ?>

<div>
    @push('breadcrumbs')
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.attributes.index')" wire:navigate>Attributes</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    @endpush

    <div>
        <flux:heading size="xl">{{ $name }}</flux:heading>
        <flux:subheading>Edit attribute details and manage its selectable values.</flux:subheading>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Values (main column) --}}
        <div class="lg:col-span-2">
            <flux:card class="p-0 overflow-hidden">
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="base">Values</flux:heading>
                    <flux:button size="sm" icon="plus" wire:click="$set('showAddValueModal', true)">New value</flux:button>
                </div>

                {{-- Existing values --}}
                @if ($this->values->isNotEmpty())
                    <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
                        <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                            <flux:table.column>Label</flux:table.column>
                            <flux:table.column>Value</flux:table.column>
                            @if ($type === 'color')
                                <flux:table.column>Colour</flux:table.column>
                            @endif
                            <flux:table.column>Sort</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column align="end">Actions</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->values as $value)
                                <flux:table.row :key="$value->id">
                                    <flux:table.cell variant="strong">{{ $value->label }}</flux:table.cell>
                                    <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $value->value }}</flux:table.cell>
                                    @if ($type === 'color')
                                        <flux:table.cell>
                                            @if ($value->color_code)
                                                <span class="inline-flex items-center gap-2">
                                                    <span class="inline-block size-4 rounded-full border border-zinc-200 dark:border-zinc-700"
                                                        style="background:{{ $value->color_code }}"></span>
                                                    <span class="font-mono text-xs text-zinc-400">{{ $value->color_code }}</span>
                                                </span>
                                            @else
                                                <span class="text-zinc-400">—</span>
                                            @endif
                                        </flux:table.cell>
                                    @endif
                                    <flux:table.cell class="tabular-nums text-zinc-500">{{ $value->sort_order }}</flux:table.cell>
                                    <flux:table.cell>
                                        <button wire:click="toggleValueActive({{ $value->id }})">
                                            <flux:badge size="sm" inset="top bottom" :color="$value->is_active ? 'green' : 'zinc'">
                                                {{ $value->is_active ? 'Active' : 'Inactive' }}
                                            </flux:badge>
                                        </button>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <div class="flex items-center justify-end gap-1">
                                            <flux:button size="xs" variant="ghost" icon="pencil-square"
                                                wire:click="openEditValue({{ $value->id }})" />
                                            <flux:button size="xs" variant="ghost" icon="trash"
                                                wire:click="deleteValue({{ $value->id }})"
                                                wire:confirm="Delete '{{ addslashes($value->label) }}'?"
                                                class="text-red-500! hover:text-red-600!" />
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif

                @if ($this->values->isEmpty())
                    <div class="py-12 text-center text-zinc-400">
                        <flux:icon.tag class="mx-auto mb-3 size-8 opacity-40" />
                        <flux:text size="sm">No values yet. Click <strong>New value</strong> to add one.</flux:text>
                    </div>
                @endif
            </flux:card>
        </div>

        {{-- Attribute details (sidebar) --}}
        <div>
            <flux:card class="p-0 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:heading size="base">Attribute details</flux:heading>
                </div>

                <form wire:submit="save" class="space-y-4 p-6">
                    <flux:input wire:model.live.debounce.400ms="name" label="Name" required />
                    <flux:input wire:model.blur="slug" label="Slug" description="Auto-generated from name." />

                    <flux:select wire:model="type" label="Type">
                        @foreach (AttributeType::cases() as $t)
                            <flux:select.option :value="$t->value" class="capitalize">{{ ucfirst($t->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="sort_order" label="Sort order" type="number" min="0" />

                    <div class="flex items-center justify-between rounded-md bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800">
                        <flux:label>Active</flux:label>
                        <flux:switch wire:model="is_active" />
                    </div>

                    <flux:button type="submit" variant="primary" class="w-full">Save changes</flux:button>
                </form>
            </flux:card>
        </div>

    </div>

    {{-- Add value modal --}}
    <flux:modal wire:model.self="showAddValueModal" class="md:w-[480px]" :dismissible="false">
        <flux:heading>New value</flux:heading>

        <form wire:submit="addValue" class="mt-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="valueLabel" label="Label" placeholder="e.g. Stainless Steel" required />
                <flux:input wire:model="valueValue" label="Value" placeholder="e.g. stainless-steel" required />
            </div>

            @if ($type === 'color')
                <flux:field>
                    <flux:label>Hex colour</flux:label>
                    <div class="flex items-center gap-2">
                        <input type="color" wire:model="valueColorCode"
                            class="h-9 w-10 cursor-pointer rounded border border-zinc-200 p-0.5 dark:border-zinc-700" />
                        <flux:input wire:model="valueColorCode" placeholder="#000000" class="w-36" />
                    </div>
                    <flux:error name="valueColorCode" />
                </flux:field>
            @endif

            <flux:input wire:model="valueSortOrder" label="Sort order" type="number" min="0" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="plus">Add value</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit value modal --}}
    <flux:modal wire:model.self="showEditValueModal" class="md:w-[480px]" :dismissible="false">
        <flux:heading>Edit value</flux:heading>

        <form wire:submit="saveValue" class="mt-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="editValueLabel" label="Label"
                    placeholder="e.g. Stainless Steel" required />
                <flux:input wire:model="editValueValue" label="Value"
                    placeholder="e.g. stainless-steel" required />
            </div>

            @if ($type === 'color')
                <flux:field>
                    <flux:label>Hex colour</flux:label>
                    <div class="flex items-center gap-2">
                        <input type="color" wire:model="editValueColorCode"
                            class="h-9 w-10 cursor-pointer rounded border border-zinc-200 p-0.5 dark:border-zinc-700" />
                        <flux:input wire:model="editValueColorCode" placeholder="#000000" class="w-36" />
                    </div>
                    <flux:error name="editValueColorCode" />
                </flux:field>
            @endif

            <flux:input wire:model="editValueSortOrder" label="Sort order" type="number" min="0" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
