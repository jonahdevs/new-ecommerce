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

<nav class="bg-brand-blue-500 text-[#f2ead9]">
    <div class="shell">
        {{-- grid-rows-2 + [grid-auto-rows:0] caps visible content at exactly 2 rows.
             Items in implicit (overflow) rows collapse to 0 height and get clipped.
             gap-px + a subtle divider color on the parent paints 1px dividers between cells. --}}
        <div
            class="grid grid-cols-2 grid-rows-2 gap-px overflow-hidden bg-brand-blue-700 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 [grid-auto-rows:0]">
            @foreach ($navCategories as $category)
                @php
                    $isActive =
                        request()->routeIs('category.show') && request()->route('category')?->id === $category->id;
                @endphp
                <a href="{{ route('category.show', $category) }}" wire:navigate @class([
                    'flex items-center gap-2 px-3 py-2.5 text-sm transition',
                    'bg-brand-blue-500 text-[#f2ead9] hover:bg-brand-blue-600 hover:text-white' => !$isActive,
                    'bg-brand-blue-700 font-medium text-white' => $isActive,
                ])>
                    @if ($category->icon_svg)
                        <span class="grid size-5 shrink-0 place-items-center [&>svg]:size-full">
                            {!! $category->icon_svg !!}
                        </span>
                    @elseif ($category->icon)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($category->icon) }}" alt=""
                            class="size-5 shrink-0 object-contain brightness-0 invert" loading="lazy" />
                    @endif
                    <span class="truncate">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</nav>
