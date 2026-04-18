<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Computed, Title};
use Spatie\Activitylog\Models\Activity;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Category Changelog')] class extends Component {
    use WithPagination;

    public Category $category;

    public function mount(int $id): void
    {
        $this->category = Category::findOrFail($id);
        $this->authorize('update', $this->category);
    }

    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        return Activity::query()->where('subject_type', Category::class)->where('subject_id', $this->category->id)->with('causer')->latest()->paginate(20);
    }

    public function render(): View
    {
        return view('livewire.admin.changelog.category-changelog');
    }

    public function getFieldLabel(string $field): string
    {
        return match ($field) {
            'name' => 'Name',
            'parent_id' => 'Parent Category',
            'status' => 'Status',
            'sort_order' => 'Sort Order',
            default => ucwords(str_replace('_', ' ', $field)),
        };
    }

    public function formatValue(mixed $value, string $field): string
    {
        if (is_null($value)) {
            return '—';
        }

        return match ($field) {
            'parent_id' => $this->getParentCategoryName($value),
            'status' => ucfirst($value),
            default => (string) $value,
        };
    }

    private function getParentCategoryName(int $parentId): string
    {
        $parent = Category::find($parentId);
        return $parent ? $parent->name : "Category #{$parentId}";
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item :href="route('admin.catalog.categories.index')" wire:navigate>Categories
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Changelog</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Category Changelog</flux:heading>
            <flux:subheading>Change history for {{ $category->name }}</flux:subheading>
        </div>
        <flux:button :href="route('admin.catalog.categories.edit', $category)" wire:navigate icon="arrow-left"
            variant="ghost">
            Back to Category
        </flux:button>
    </div>

    <flux:card class="p-0">
        @if ($this->activities->isEmpty())
            <div class="py-16 text-center">
                <div class="flex flex-col items-center gap-3">
                    <flux:icon.clock class="size-10 text-zinc-300 dark:text-zinc-600" />
                    <div>
                        <flux:heading size="sm">No changes recorded</flux:heading>
                        <flux:subheading class="mt-0.5">
                            Changes to this category will appear here.
                        </flux:subheading>
                    </div>
                </div>
            </div>
        @else
            <flux:table :paginate="$this->activities">
                <flux:table.columns>
                    <flux:table.column class="ps-5! w-48">Timestamp</flux:table.column>
                    <flux:table.column class="w-48">Changed By</flux:table.column>
                    <flux:table.column>Changes</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->activities as $activity)
                        <flux:table.row :key="$activity->id">
                            {{-- Timestamp --}}
                            <flux:table.cell class="ps-5! align-top">
                                <div class="text-sm text-zinc-800 dark:text-zinc-100">
                                    {{ $activity->created_at->format('M j, Y') }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    {{ $activity->created_at->format('g:i A') }}
                                </div>
                            </flux:table.cell>

                            {{-- Changed By --}}
                            <flux:table.cell class="align-top">
                                @if ($activity->causer)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="xs" circle :name="$activity->causer->name" />
                                        <div>
                                            <div class="text-sm text-zinc-800 dark:text-zinc-100">
                                                {{ $activity->causer->name }}</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ $activity->causer->email }}</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                                        <flux:icon name="cog-6-tooth" class="size-4" />
                                        <span class="text-sm">System</span>
                                    </div>
                                @endif
                            </flux:table.cell>

                            {{-- Changes --}}
                            <flux:table.cell class="align-top">
                                <div class="space-y-2">
                                    @php
                                        $oldValues = $activity->properties['old'] ?? [];
                                        $newValues = $activity->properties['attributes'] ?? [];
                                        $changedFields = array_unique(
                                            array_merge(array_keys($oldValues), array_keys($newValues)),
                                        );
                                    @endphp

                                    @foreach ($changedFields as $field)
                                        <div class="text-sm">
                                            <span
                                                class="font-medium text-zinc-700 dark:text-zinc-300">{{ $this->getFieldLabel($field) }}:</span>
                                            <span class="text-zinc-600 dark:text-zinc-400">
                                                {{ $this->formatValue($oldValues[$field] ?? null, $field) }}
                                            </span>
                                            <span class="text-zinc-400 dark:text-zinc-500 mx-1">→</span>
                                            <span class="text-zinc-800 dark:text-zinc-100 font-medium">
                                                {{ $this->formatValue($newValues[$field] ?? null, $field) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
