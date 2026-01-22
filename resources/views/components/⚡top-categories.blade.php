<?php

use App\Models\Category;
use Livewire\Component;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Computed;

new #[Defer] class extends Component {
    #[Computed]
    public function topCategories()
    {
        return Category::active()->featured()->ordered()->get();
    }
};
?>

@placeholder
    <div class="bg-white border rounded-sm">
        <div class="py-4 px-3 md:px-5">
            <!-- Responsive Heading -->
            <h2 class="font-semibold text-xl text-zinc-800 ">
                Top Categories
            </h2>
        </div>
        <div
            class="px-3 md:px-5 py-3 pb-5 grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 gap-3">
            @for ($i = 0; $i < 14; $i++)
                <div class="animate-pulse">
                    <div class="w-full aspect-4/3 bg-zinc-200 rounded-md"></div>
                    <div class="w-3/4 h-3 sm:h-4 mt-2 bg-zinc-200 mx-auto rounded"></div>
                </div>
            @endfor
        </div>
    </div>
@endplaceholder


<div>
    <div class="bg-white border rounded-sm">
        <div class="py-4 px-3 md:px-5">
            <!-- Responsive Heading -->
            <h2 class="font-semibold text-xl text-zinc-800 ">
                Top Categories
            </h2>
        </div>

        <div
            class="px-3 md:px-5 py-3 pb-5 grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 gap-3">
            @foreach ($this->topCategories as $category)
                <div class="group/card">
                    <a href="#" wire:navigate>
                        <div class="relative w-full rounded-md overflow-hidden">
                            @if ($category->image_url)
                                <figure
                                    class="w-full bg-zinc-100 rounded-md aspect-4/3 shadow-sm hover:shadow-md transition-shadow duration-300">
                                    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy"
                                        class="object-cover object-center w-full h-full transition-transform duration-300 group-hover/card:scale-110">
                                </figure>
                            @else
                                <div
                                    class="w-full h-full flex items-center justify-center bg-zinc-100 rounded-md aspect-4/3 shadow-sm hover:shadow-md transition-shadow duration-300">
                                    <flux:icon.photo class="text-zinc-400 h-8 w-8 sm:h-10 sm:w-10 lg:h-12 lg:w-12" />
                                </div>
                            @endif
                        </div>

                        <div
                            class="mt-2 sm:mt-3 text-wrap wrap-break-words text-center text-xs sm:text-sm font-medium text-zinc-700 group-hover/card:text-sheffield-blue transition-colors duration-200">
                            {{ $category->name }}
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
