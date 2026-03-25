<div class="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 gap-2.5">
    @foreach ($this->topCategories as $category)
        <div class="group relative" :key="'category-' . $category->id">
            <a href="{{ route('shop.category', ['category' => $category->slug]) }}" wire:navigate class="block">
                <div @class([
                    'relative aspect-4/3 overflow-hidden rounded-md bg-zinc-50 transition-all duration-300 group-hover:shadow-md',
                    'border border-zinc-200' => !$category->image_url,
                ])>
                    @if ($category->image_url)
                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy"
                            class="object-cover w-full h-full transition-transform duration-500 group-hover:scale-105">
                    @else
                        <div class="flex items-center justify-center h-full">
                            <flux:icon.photo class="text-zinc-300 h-10 w-10 stroke-1" />
                        </div>
                    @endif

                    <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity">
                    </div>
                </div>

                <p
                    class="mt-3 text-center text-sm font-semibold text-zinc-800 group-hover:text-sheffield-blue transition-colors line-clamp-1">
                    {{ $category->name }}
                </p>
            </a>
        </div>
    @endforeach
</div>
