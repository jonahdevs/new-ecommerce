<?php
// Extract directive's "with" parameter (overrides component properties)
$__islandScope = (function($name = null, $token = null, $lazy = false, $defer = false, $always = false, $skip = false, $with = []) {
    return $with;
})('top-categories');
if (!empty($__islandScope)) {
    extract($__islandScope, EXTR_OVERWRITE);
}

// Extract runtime "with" parameter if provided (overrides everything)
if (isset($__runtimeWith) && is_array($__runtimeWith) && !empty($__runtimeWith)) {
    extract($__runtimeWith, EXTR_OVERWRITE);
}
?>

            <?php if (isset($__placeholder)) { ob_start(); } if (isset($__placeholder)): ?>
                <div
                    class="py-3 pb-5 grid grid-cols-2 @xs/categories:grid-cols-3 @md/categories:grid-cols-4 @2xl/categories:grid-cols-5 @4xl/categories:grid-cols-6 @6xl/categories:grid-cols-7 gap-3">
                    @for ($i = 0; $i < 14; $i++)
                        <div class="animate-pulse">
                            <div class="w-full aspect-4/3 bg-zinc-200 rounded-md"></div>
                            <div class="w-3/4 h-3 sm:h-4 mt-2 bg-zinc-200 mx-auto rounded"></div>
                        </div>
                    @endfor
                </div>
            <?php endif; if (isset($__placeholder)) { echo ob_get_clean(); return; } ?>

            <div
                class="grid grid-cols-2 @xs/categories:grid-cols-3 @md/categories:grid-cols-4 @2xl/categories:grid-cols-5 @4xl/categories:grid-cols-6 @6xl/categories:grid-cols-7 gap-x-3 gap-y-6 @md/categories:gap-x-5 @md/categories:gap-y-10">
                @foreach ($this->topCategories as $category)
                    <div class="group relative" :key="'category-' . $category->id">
                        <a href="{{ route('shop.category', ['category' => $category->slug]) }}" wire:navigate
                            class="block">
                            <div @class([
                                'relative aspect-4/3 overflow-hidden rounded-md bg-zinc-50',
                                'border border-zinc-200' => !$category->image_url,
                            ])>
                                @if ($category->image_url)
                                    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy"
                                        class="object-cover w-full h-full">
                                @else
                                    <div class="flex items-center justify-center h-full">
                                        <flux:icon.photo class="text-zinc-300 h-10 w-10 stroke-1" />
                                    </div>
                                @endif
                            </div>

                            <p class="mt-5 text-center text-sm font-semibold">
                                {{ $category->name }}
                            </p>
                        </a>
                    </div>
                @endforeach
            </div>
        