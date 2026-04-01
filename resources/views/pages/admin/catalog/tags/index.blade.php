<?php

use App\Models\Tag;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};

new #[Title('Tags')] class extends Component {
    use WithPagination;

    public string $search = '';
    public ?string $typeFilter = null;

    public function delete($id): void
    {
        try {
            $tag = Tag::findOrFail($id);

            $productsCount = $tag->products()->count();

            if ($productsCount > 0) {
                $this->dispatch('notify', title: 'Delete Failed', variant: 'warning', message: "This tag is used by {$productsCount} product(s) and cannot be deleted");
                return;
            }

            $tag->delete();

            $this->dispatch('notify', title: 'Tag Deleted', variant: 'success', message: 'The tag has been deleted successfully');
        } catch (\Throwable $th) {
            \Log::error('Error deleting tag: ' . $th->getMessage(), ['exception' => $th]);

            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Failed to delete tag. Please try again');
        }
    }

    #[Computed]
    public function tags()
    {
        return Tag::query()->withCount('products')->when($this->search, fn($q) => $q->where('name->en', 'like', "%{$this->search}%"))->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))->orderBy('order_column')->paginate(10);
    }

    #[Computed]
    public function types()
    {
        return Tag::query()->whereNotNull('type')->distinct()->pluck('type');
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Tags</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-1">Tags</flux:heading>
            <flux:subheading>Organize and categorize your products with tags.</flux:subheading>
        </div>
        <flux:button href="{{ route('admin.catalog.tags.create') }}" variant="primary" icon="plus-circle" wire:navigate>
            Create Tag
        </flux:button>
    </div>


    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <flux:card>
            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Total Tags</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ Tag::count() }}</div>
        </flux:card>
        <flux:card>
            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Tag Types</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->types->count() }}</div>
        </flux:card>
        <flux:card>
            <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Total Tagged Products</div>
            <div class="text-2xl font-bold text-zinc-500 dark:text-zinc-400">
                {{ \DB::table('taggables')->distinct('taggable_id')->count() }}</div>
        </flux:card>
    </div>

    {{-- Type Filter --}}
    @if ($this->types->isNotEmpty())
        <div class="mt-4 border-b border-zinc-200 dark:border-zinc-600">
            <nav class="flex gap-1 overflow-x-auto">
                <button type="button" wire:click="$set('typeFilter', null)" @class([
                    'inline-flex items-center gap-1.5 px-3 py-2 text-sm whitespace-nowrap transition-colors duration-150 cursor-pointer',
                    'bg-brand-primary text-brand-primary-content font-medium' =>
                        $typeFilter === null,
                    'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800' =>
                        $typeFilter !== null,
                ])>
                    All Types
                </button>
                @foreach ($this->types as $type)
                    <button type="button" wire:click="$set('typeFilter', '{{ $type }}')"
                        @class([
                            'inline-flex items-center gap-1.5 px-3 py-2 text-sm whitespace-nowrap transition-colors duration-150 cursor-pointer',
                            'bg-brand-primary text-brand-primary-content font-medium' =>
                                $typeFilter === $type,
                            'text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800' =>
                                $typeFilter !== $type,
                        ])>
                        {{ ucfirst($type) }}
                    </button>
                @endforeach
            </nav>
        </div>
    @endif

    {{-- Tags Table --}}
    <flux:card class="p-0 mt-5 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-600">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search tags by name..." class="max-w-md" />
        </div>

        <flux:table :paginate="$this->tags">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Tag</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Products</flux:table.column>
                <flux:table.column>Order</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->tags as $tag)
                    <flux:table.row :key="$tag->id">
                        <flux:table.cell class="ps-4!">
                            <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $tag->name }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="font-mono text-sm text-zinc-600 dark:text-zinc-400">{{ $tag->slug }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($tag->type)
                                <flux:badge size="sm" color="blue">{{ ucfirst($tag->type) }}</flux:badge>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500 text-sm">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">
                                {{ $tag->products_count }} {{ Str::plural('product', $tag->products_count) }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-zinc-700 dark:text-zinc-300">{{ $tag->order_column ?? 0 }}</span>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                href="{{ route('admin.catalog.tags.edit', $tag) }}" wire:navigate tooltip="Edit Tag" />

                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                class="text-red-500 dark:text-red-400!"
                                wire:confirm="Delete this tag? This action cannot be undone if the tag is not used by any products."
                                wire:click="delete({{ $tag->id }})" tooltip="Delete Tag" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-500 dark:text-zinc-400 py-8">
                            No tags found
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
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
