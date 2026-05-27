@php
    // TODO: extract into a view composer + cache once this becomes hot
    $footerCategories = \App\Models\CategoryPlacement::query()
        ->with('category')
        ->where('location', \App\Enums\CategorySection::FOOTER)
        ->where('status', \App\Enums\CategoryStatus::ACTIVE)
        ->orderBy('sort_order')
        ->get()
        ->pluck('category')
        ->filter();
@endphp

<footer class="mt-16 border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950">
    <div class="mx-auto max-w-7xl px-6 py-12">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-12">
            <div class="md:col-span-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
                    <x-app-logo-icon class="size-7 fill-current text-black dark:text-white" />
                    <span class="text-base font-semibold">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <p class="mt-3 max-w-xs text-sm text-zinc-600 dark:text-zinc-400">
                    Commercial kitchen and food-service equipment for restaurants, hotels, and catering operations across East Africa.
                </p>
            </div>

            <div class="md:col-span-3">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Shop</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($footerCategories as $category)
                        <li>
                            <a href="#" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100" wire:navigate>
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="md:col-span-2">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Company</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="#" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">About</a></li>
                    <li><a href="#" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Contact</a></li>
                    <li><a href="#" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Showroom</a></li>
                </ul>
            </div>

            <div class="md:col-span-3">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Support</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="#" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Shipping & Delivery</a></li>
                    <li><a href="#" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Returns</a></li>
                    <li><a href="#" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Warranty</a></li>
                    <li><a href="#" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">FAQ</a></li>
                </ul>
            </div>
        </div>

        <flux:separator class="my-8" />

        <div class="flex flex-col items-center justify-between gap-4 text-xs text-zinc-500 sm:flex-row">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <a href="#" class="hover:text-zinc-900 dark:hover:text-zinc-100">Privacy</a>
                <a href="#" class="hover:text-zinc-900 dark:hover:text-zinc-100">Terms</a>
            </div>
        </div>
    </div>
</footer>
