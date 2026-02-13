<?php
use App\Models\Tag;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};

new #[Title('Tags')] class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';

    public function delete($id)
    {
        $tag = Tag::findOrFail($id);

        // Check if tag is used by products
        $productsCount = $tag->products()->count();

        if ($productsCount > 0) {
            session()->flash('error', "Cannot delete tag. It's used by {$productsCount} product(s).");
            return;
        }

        $tag->delete();
        session()->flash('status', 'Tag deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->update(['is_active' => !$tag->is_active]);

        session()->flash('status', 'Tag status updated.');
    }

    #[Computed]
    public function tags()
    {
        return Tag::query()
            ->withCount('products')
            ->when($this->search, function ($q) {
                $q->where('name', 'like', "%{$this->search}%")->orWhere('slug', 'like', "%{$this->search}%");
            })
            ->when($this->statusFilter === 'active', fn($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function statusCounts()
    {
        return [
            'all' => Tag::count(),
            'active' => Tag::where('is_active', true)->count(),
            'inactive' => Tag::where('is_active', false)->count(),
        ];
    }
};
?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-1">Tags</flux:heading>
            <flux:subheading>Organize and categorize your products with tags.</flux:subheading>
        </div>

        <flux:button href="{{ route('admin.tags.create') }}" variant="primary" icon="plus" wire:navigate>
            Create Tag
        </flux:button>
    </div>

    {{-- Flash Messages --}}
    @if (session('status'))
        <flux:callout variant="success" class="mb-6">
            {{ session('status') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" class="mb-6">
            {{ session('error') }}
        </flux:callout>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <flux:card>
            <div class="text-sm text-zinc-600 mb-1">Total Tags</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->statusCounts['all'] }}</div>
        </flux:card>

        <flux:card>
            <div class="text-sm text-zinc-600 mb-1">Active Tags</div>
            <div class="text-2xl font-bold text-green-600">{{ $this->statusCounts['active'] }}</div>
        </flux:card>

        <flux:card>
            <div class="text-sm text-zinc-600 mb-1">Inactive Tags</div>
            <div class="text-2xl font-bold text-zinc-500">{{ $this->statusCounts['inactive'] }}</div>
        </flux:card>
    </div>

    {{-- Filter Tabs --}}
    <div class="flex gap-2 mb-6">
        <flux:button wire:click="$set('statusFilter', 'all')"
            variant="{{ $statusFilter === 'all' ? 'primary' : 'ghost' }}" size="sm" class="cursor-pointer">
            All Tags
            <flux:badge size="sm" :color="$statusFilter === 'all' ? 'white' : 'zinc'">
                {{ $this->statusCounts['all'] }}
            </flux:badge>
        </flux:button>

        <flux:button wire:click="$set('statusFilter', 'active')"
            variant="{{ $statusFilter === 'active' ? 'primary' : 'ghost' }}" size="sm" class="cursor-pointer">
            Active
            <flux:badge size="sm" :color="$statusFilter === 'active' ? 'white' : 'zinc'">
                {{ $this->statusCounts['active'] }}
            </flux:badge>
        </flux:button>

        <flux:button wire:click="$set('statusFilter', 'inactive')"
            variant="{{ $statusFilter === 'inactive' ? 'primary' : 'ghost' }}" size="sm" class="cursor-pointer">
            Inactive
            <flux:badge size="sm" :color="$statusFilter === 'inactive' ? 'white' : 'zinc'">
                {{ $this->statusCounts['inactive'] }}
            </flux:badge>
        </flux:button>
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
            placeholder="Search tags by name or slug..." class="max-w-md" />
    </div>

    {{-- Tags Table --}}
    <flux:table :paginate="$this->tags">
        <flux:table.columns>
            <flux:table.column>Tag</flux:table.column>
            <flux:table.column>Slug</flux:table.column>
            <flux:table.column>Products</flux:table.column>
            <flux:table.column>Sort Order</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->tags as $tag)
                <flux:table.row :key="$tag->id">
                    {{-- Tag Name with Color --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded-full border"
                                style="background-color: {{ $tag->color ?? '#6B7280' }}">
                            </div>
                            <div>
                                <div class="font-medium text-zinc-800 dark:text-white">{{ $tag->name }}</div>
                                @if ($tag->description)
                                    <div class="text-xs text-zinc-500">{{ Str::limit($tag->description, 50) }}</div>
                                @endif
                            </div>
                        </div>
                    </flux:table.cell>

                    {{-- Slug --}}
                    <flux:table.cell>
                        <span class="font-mono text-sm text-zinc-600">{{ $tag->slug }}</span>
                    </flux:table.cell>

                    {{-- Products Count --}}
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">
                            {{ $tag->products_count }} {{ Str::plural('product', $tag->products_count) }}
                        </flux:badge>
                    </flux:table.cell>

                    {{-- Sort Order --}}
                    <flux:table.cell>
                        {{ $tag->sort_order ?? 0 }}
                    </flux:table.cell>

                    {{-- Status --}}
                    <flux:table.cell>
                        <button wire:click="toggleStatus({{ $tag->id }})" class="inline-flex">
                            <flux:badge size="sm" variant="flat" :color="$tag->is_active ? 'green' : 'gray'">
                                {{ $tag->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </button>
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell align="end">
                        <flux:button variant="ghost" size="sm" icon="pencil-square"
                            href="{{ route('admin.tags.edit', $tag) }}" wire:navigate />

                        <flux:button variant="ghost" size="sm" icon="trash" color="red"
                            wire:confirm="Delete this tag? This action cannot be undone if the tag is not used by any products."
                            wire:click="delete({{ $tag->id }})" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 py-8">
                        No tags found
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
