<?php

use App\Models\TaxClass;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Tax classes | Admin')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public int $perPage = 10;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public ?float $rate = 16.0;

    public string $description = '';

    public bool $is_active = true;

    private bool $slugManuallyEdited = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function taxClasses()
    {
        return TaxClass::withCount('products')
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate($this->perPage);
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

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'description']);
        $this->rate = 16.0;
        $this->is_active = true;
        $this->slugManuallyEdited = false;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $taxClass = TaxClass::findOrFail($id);
        $this->editingId = $id;
        $this->name = $taxClass->name;
        $this->slug = $taxClass->slug;
        $this->rate = (float) $taxClass->rate;
        $this->description = (string) $taxClass->description;
        $this->is_active = (bool) $taxClass->is_active;
        $this->slugManuallyEdited = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('tax_classes', 'slug')->ignore($this->editingId)],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'rate' => $this->rate,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            TaxClass::findOrFail($this->editingId)->update($data);
            Flux::toast(heading: 'Tax class updated', text: $this->name.' has been saved.', variant: 'success');
        } else {
            TaxClass::create($data);
            Flux::toast(heading: 'Tax class created', text: $this->name.' has been added.', variant: 'success');
        }

        $this->showModal = false;
        unset($this->taxClasses);
    }

    public function toggleActive(int $id): void
    {
        $taxClass = TaxClass::findOrFail($id);
        $taxClass->update(['is_active' => ! $taxClass->is_active]);
        unset($this->taxClasses);
    }

    public function delete(int $id): void
    {
        $taxClass = TaxClass::withCount('products')->findOrFail($id);

        if ($taxClass->products_count > 0) {
            Flux::toast(heading: 'Cannot delete', text: $taxClass->name.' is assigned to '.$taxClass->products_count.' product(s).', variant: 'danger');

            return;
        }

        if (app(\App\Settings\TaxSettings::class)->default_tax_class_id === $id) {
            Flux::toast(heading: 'Cannot delete', text: $taxClass->name.' is the store default tax class. Choose another default first.', variant: 'danger');

            return;
        }

        $taxClass->delete();
        unset($this->taxClasses);
        Flux::toast(heading: 'Tax class deleted', text: $taxClass->name.' has been removed.', variant: 'success');
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Tax classes</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush
            <flux:heading size="xl">Tax classes</flux:heading>
            <flux:subheading>VAT rates you can assign to products. Products without a class use the store default rate.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">Add tax class</flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search tax classes…" icon="magnifying-glass"
                clearable class="max-w-xs" />

            <flux:select wire:model.live="perPage" class="w-28">
                <flux:select.option value="10">10 / page</flux:select.option>
                <flux:select.option value="25">25 / page</flux:select.option>
                <flux:select.option value="50">50 / page</flux:select.option>
            </flux:select>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Tax class</flux:table.column>
                <flux:table.column>Rate</flux:table.column>
                <flux:table.column>Products</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->taxClasses as $taxClass)
                    <flux:table.row :key="$taxClass->id">
                        <flux:table.cell variant="strong">
                            {{ $taxClass->name }}
                            <span class="block font-mono text-xs font-normal text-zinc-400">{{ $taxClass->slug }}</span>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ rtrim(rtrim(number_format((float) $taxClass->rate, 2), '0'), '.') }}%</flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ $taxClass->products_count }}</flux:table.cell>
                        <flux:table.cell>
                            <button wire:click="toggleActive({{ $taxClass->id }})">
                                <flux:badge size="sm" inset="top bottom" :color="$taxClass->is_active ? 'green' : 'zinc'">
                                    {{ $taxClass->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Activity log">
                                    <flux:button size="xs" variant="ghost" icon="clock"
                                        :href="route('admin.activity.item', ['tax_class', $taxClass->id])"
                                        wire:navigate />
                                </flux:tooltip>
                                <flux:button size="xs" variant="ghost" icon="pencil-square"
                                    wire:click="openEdit({{ $taxClass->id }})" />
                                <flux:button size="xs" variant="ghost" icon="trash-2"
                                    wire:click="delete({{ $taxClass->id }})"
                                    wire:confirm="Delete '{{ addslashes($taxClass->name) }}'?"
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-12 text-center text-zinc-400">
                            No tax classes yet.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->taxClasses->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->taxClasses" />
            </div>
        @endif
    </flux:card>

    {{-- Modal --}}
    <flux:modal wire:model.self="showModal" class="md:w-[480px]" :dismissible="false">
        <flux:heading class="uppercase tracking-wide">{{ $editingId ? 'Edit tax class' : 'New tax class' }}</flux:heading>

        <form wire:submit="save" class="mt-5 space-y-4">
            <flux:input wire:model.live.debounce.400ms="name" label="Name" placeholder="e.g. Standard VAT" required />
            <flux:input wire:model.blur="slug" label="Slug" description="Auto-generated from name." />
            <flux:input wire:model="rate" label="Rate (%)" type="number" min="0" max="100" step="0.01" required />
            <flux:textarea wire:model="description" label="Description" rows="2"
                placeholder="e.g. Standard-rated goods at 16% VAT." />
            <flux:switch wire:model="is_active" label="Active" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? 'Save changes' : 'Create tax class' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
