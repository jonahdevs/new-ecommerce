<?php
use App\Models\Brand;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};

new #[Title('Brands')] class extends Component {
    use WithPagination;

    public $search = '';
    public ?int $brandToDelete = null;
    public ?string $brandNameToDelete = null;

    public function confirmDelete($id, $name)
    {
        $this->brandToDelete = $id;
        $this->brandNameToDelete = $name;
        $this->modal('delete-brand')->show();
    }

    public function delete()
    {
        try {
            if ($this->brandToDelete) {
                $brand = Brand::findOrFail($this->brandToDelete);
                $brand->delete();
                $this->modal('delete-brand')->close();
                $this->dispatch('notify', title: 'Brand Deleted', variant: 'success', message: 'Brand deleted successfully!');
                $this->brandToDelete = null;
                $this->brandNameToDelete = null;
            }
        } catch (\Throwable $th) {
            \Log::error('Error deleting brand: ' . $th->getMessage(), ['exception' => $th]);
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Failed to delete brand.');
        }
    }

    #[Computed]
    public function brands()
    {
        return Brand::query()->withCount('products')->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))->latest()->paginate(10);
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Brands</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl">Brands</flux:heading>
            <flux:subheading>Manage product brands and manufacturers</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus-circle" :href="route('admin.catalog.brands.create')" wire:navigate>
            Create Brand
        </flux:button>
    </div>


    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="px-5 py-3 border-b dark:border-zinc-600">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search brands..."
                class="max-w-md" clearable />
        </div>

        <flux:table :paginate="$this->brands">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Brand</flux:table.column>
                <flux:table.column>Website</flux:table.column>
                <flux:table.column>Products</flux:table.column>
                <flux:table.column>Sort Order</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->brands as $brand)
                    <flux:table.row :key="$brand->id">
                        <flux:table.cell class="flex items-center gap-3 ps-4!">
                            @if ($brand->logo_path)
                                <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }} Logo"
                                    class="w-10 h-10 rounded object-contain p-1 border">
                            @else
                                <div
                                    class="w-10 h-10 bg-zinc-50 dark:bg-zinc-800 border dark:border-zinc-600 flex items-center justify-center rounded">
                                    <flux:icon name="building-storefront" variant="micro" />
                                </div>
                            @endif

                            <div>
                                <flux:heading>{{ $brand->name }}</flux:heading>
                                <flux:text class="text-xs">{{ $brand->slug }}</flux:text>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($brand->website_url)
                                <a href="{{ $brand->website_url }}" target="_blank" rel="noopener noreferrer"
                                    class="text-blue-600 hover:underline text-sm flex items-center gap-1">
                                    Visit
                                    <flux:icon name="arrow-top-right-on-square" variant="micro" />
                                </a>
                            @else
                                <flux:text>--</flux:text>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc" variant="subtle">
                                {{ $brand->products_count }} products
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text>{{ $brand->sort_order }}</flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" :color="$brand->is_active ? 'green' : 'red'" variant="flat">
                                {{ $brand->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                <flux:menu>
                                    {{-- Edit --}}
                                    <flux:menu.item icon="pencil-square" icon-variant="outline"
                                        href="{{ route('admin.catalog.brands.edit', $brand->id) }}" wire:navigate>
                                        Edit
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    {{-- Change Log --}}
                                    <flux:menu.item icon="clock" icon-variant="outline"
                                        href="{{ route('admin.changelog.brand', $brand) }}" wire:navigate>
                                        Change Log
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    {{-- Delete --}}
                                    <flux:menu.item icon="trash" icon-variant="outline" color="red"
                                        wire:click="confirmDelete({{ $brand->id }}, '{{ $brand->name }}')">
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-brand" class="md:w-96">
        <flux:heading size="lg" class="mb-2">Delete Brand</flux:heading>
        <form wire:submit="delete" class="space-y-6">
            <div>
                <flux:subheading>
                    @if ($brandNameToDelete)
                        <p class="mt-2">Are you sure you want to delete <strong>{{ $brandNameToDelete }}</strong>?
                        </p>
                        <p class="mt-1 text-sm text-red-600">This action cannot be undone.</p>
                    @endif
                </flux:subheading>
            </div>

            <div class="flex gap-2 justify-evenly">
                <flux:button type="button" variant="ghost" wire:click="$dispatch('modal-close', 'delete-brand')"
                    class="w-full">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="danger" class="cursor-pointer w-full">
                    Delete Brand
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

<style>
    [data-flux-pagination] {
        padding-inline: 1rem;
        padding-bottom: 1rem;
    }
</style>
