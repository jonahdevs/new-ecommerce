@props(['category'])

<div class="border rounded-lg p-2 bg-white dark:bg-zinc-800">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            @if ($category->image_icon)
                <img src="{{ asset('storage/' . $category->image_icon) }}" class="w-8 h-8 rounded shadow-sm">
            @elseif($category->icon_svg)
                <div class="w-8 h-8 flex items-center justify-center">
                    {!! $category->icon_svg !!}
                </div>
            @endif

            <div>
                <flux:text font="medium">{{ $category->name }}</flux:text>
                <flux:text size="sm" class="text-zinc-500">{{ $category->slug }}</flux:text>
            </div>
        </div>

        <div class="flex gap-2">
            <flux:button size="sm" variant="ghost" icon="pencil-square"
                wire:click="$dispatchTo('admin.categories.category-form', 'editCategory', { id: {{ $category->id }} })" />

            <flux:button size="sm" variant="ghost" icon="trash" color="red"
                wire:confirm="Are you sure you want to delete this category and all its subcategories?"
                wire:click="deleteCategory({{ $category->id }})" />
        </div>
    </div>

    {{-- Recursive Step: If children exist, indent and render them --}}
    @if ($category->children->count() > 0)
        <div class="ml-8 mt-2 space-y-2 border-l-2 border-zinc-100 pl-4">
            @foreach ($category->children as $child)
                <x-admin.category-item :category="$child" />
            @endforeach
        </div>
    @endif
</div>
