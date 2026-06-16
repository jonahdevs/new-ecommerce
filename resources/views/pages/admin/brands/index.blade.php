<?php

use App\Models\Brand;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Brands — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public int $perPage = 10;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $website_url = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    private bool $slugManuallyEdited = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function brands()
    {
        return Brand::withCount('products')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->filterStatus !== '', fn($q) => $q->where('is_active', $this->filterStatus === 'active'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function updatedName(): void
    {
        if (!$this->slugManuallyEdited) {
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
        $this->reset(['editingId', 'name', 'slug', 'description', 'website_url']);
        $this->is_active = true;
        $this->sort_order = 0;
        $this->slugManuallyEdited = false;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $brand = Brand::findOrFail($id);
        $this->editingId = $id;
        $this->name = $brand->name;
        $this->slug = $brand->slug;
        $this->description = (string) $brand->description;
        $this->website_url = (string) $brand->website_url;
        $this->is_active = (bool) $brand->is_active;
        $this->sort_order = (int) $brand->sort_order;
        $this->slugManuallyEdited = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($this->editingId)],
            'website_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'website_url' => $this->website_url ?: null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingId) {
            Brand::findOrFail($this->editingId)->update($data);
            Flux::toast(heading: 'Brand updated', text: $this->name . ' has been saved.', variant: 'success');
        } else {
            Brand::create($data);
            Flux::toast(heading: 'Brand created', text: $this->name . ' has been added.', variant: 'success');
        }

        $this->showModal = false;
        unset($this->brands);
    }

    public function toggleActive(int $id): void
    {
        $brand = Brand::findOrFail($id);
        $brand->update(['is_active' => !$brand->is_active]);
        unset($this->brands);
    }

    public function delete(int $id): void
    {
        $brand = Brand::withCount('products')->findOrFail($id);

        if ($brand->products_count > 0) {
            Flux::toast(heading: 'Cannot delete', text: $brand->name . ' has ' . $brand->products_count . ' products attached.', variant: 'danger');

            return;
        }

        $brand->delete();
        unset($this->brands);
        Flux::toast(heading: 'Brand deleted', text: $brand->name . ' has been removed.', variant: 'success');
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
<flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Brands</flux:breadcrumbs.item>
            </flux:breadcrumbs>
@endpush
            <flux:heading size="xl">Brands</flux:heading>
            <flux:subheading>Manufacturers and labels carried in your catalog.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">Add brand</flux:button>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search brands…" icon="magnifying-glass"
                clearable class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterStatus" class="w-36">
                    <flux:select.option value="">All statuses</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="inactive">Inactive</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="10">10 / page</flux:select.option>
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                    <flux:select.option value="100">100 / page</flux:select.option>
                    <flux:select.option value="250">250 / page</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Brand</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Website</flux:table.column>
                <flux:table.column>Products</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->brands as $brand)
                    <flux:table.row :key="$brand->id">
                        <flux:table.cell variant="strong">{{ $brand->name }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs text-zinc-400">{{ $brand->slug }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">
                            @if ($brand->website_url)
                                <a href="{{ $brand->website_url }}" target="_blank"
                                    class="flex items-center gap-1 hover:text-brand-500">
                                    {{ parse_url($brand->website_url, PHP_URL_HOST) }}
                                    <flux:icon.arrow-top-right-on-square variant="micro" class="size-3" />
                                </a>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">
                            {{ $brand->products_count }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <button wire:click="toggleActive({{ $brand->id }})">
                                <flux:badge size="sm" inset="top bottom"
                                    :color="$brand->is_active ? 'green' : 'zinc'">
                                    {{ $brand->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </button>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:tooltip content="Activity log">
                                    <flux:button size="xs" variant="ghost" icon="clock"
                                        :href="route('admin.activity.item', ['brand', $brand->id])"
                                        wire:navigate />
                                </flux:tooltip>
                                <flux:button size="xs" variant="ghost" icon="pencil-square"
                                    wire:click="openEdit({{ $brand->id }})" />
                                <flux:button size="xs" variant="ghost" icon="trash-2"
                                    wire:click="delete({{ $brand->id }})"
                                    wire:confirm="Delete '{{ addslashes($brand->name) }}'?"
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center text-zinc-400">
                            No brands found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->brands->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->brands" />
            </div>
        @endif
    </flux:card>

    {{-- Modal --}}
    <flux:modal wire:model.self="showModal" class="md:w-[520px]" :dismissible="false">
        <flux:heading class="uppercase tracking-wide">{{ $editingId ? 'Edit brand' : 'New brand' }}</flux:heading>

        <form wire:submit="save" class="mt-5 space-y-4">
            <flux:input wire:model.live.debounce.400ms="name" label="Name" placeholder="e.g. Rational" required />
            <flux:input wire:model.blur="slug" label="Slug" description="Auto-generated from name." />
            <flux:textarea wire:model="description" label="Description" rows="3" />
            <flux:input wire:model="website_url" label="Website" type="url" placeholder="https://example.com" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="sort_order" label="Sort order" type="number" min="0" />
                <div class="flex items-end pb-1">
                    <flux:switch wire:model="is_active" label="Active" />
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? 'Save changes' : 'Create brand' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
