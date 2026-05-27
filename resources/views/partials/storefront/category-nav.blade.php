@php
    // TODO: extract into a view composer + cache once this becomes hot.
    // 12 = max grid capacity (6 cols × 2 rows at lg). Smaller breakpoints clip via overflow.
    $navCategories = \App\Models\CategoryPlacement::query()
        ->with('category')
        ->where('location', \App\Enums\CategorySection::NAVBAR)
        ->where('status', \App\Enums\CategoryStatus::ACTIVE)
        ->orderBy('sort_order')
        ->take(12)
        ->get()
        ->pluck('category')
        ->filter();
@endphp

<nav class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mx-auto max-w-7xl px-6">
        {{-- grid-rows-2 + [grid-auto-rows:0] caps visible content at exactly 2 rows.
             Items in implicit (overflow) rows collapse to 0 height and get clipped.
             gap-px + bg on parent paints 1px dividers between cells. --}}
        <div class="grid grid-cols-2 grid-rows-2 gap-px overflow-hidden border-x border-zinc-200 bg-zinc-200 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 dark:border-zinc-800 dark:bg-zinc-800 [grid-auto-rows:0]">
            @foreach ($navCategories as $category)
                <a
                    href="#"
                    wire:navigate
                    @class([
                        'flex items-center gap-2 bg-white px-3 py-2.5 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800',
                        'font-medium text-brand-500' => request()->routeIs('category.show') && request()->route('category')?->id === $category->id,
                    ])
                >
                    @if ($category->icon_svg)
                        <span class="grid size-5 shrink-0 place-items-center text-zinc-600 [&>svg]:size-full dark:text-zinc-300">
                            {!! $category->icon_svg !!}
                        </span>
                    @elseif ($category->icon)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($category->icon) }}" alt="" class="size-5 shrink-0 object-contain" loading="lazy" />
                    @endif
                    <span class="truncate">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</nav>
