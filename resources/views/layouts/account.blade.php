<x-layouts::storefront :title="$title ?? null">
    <div class="shell pt-4 pb-10">
        @stack('breadcrumbs')
        <div class="mt-6 flex flex-col gap-8 lg:flex-row lg:items-start">

            {{-- Sidebar --}}
            <aside class="w-full shrink-0 lg:w-64">
                <div class="overflow-hidden rounded-md border border-zinc-200 bg-white">

                    {{-- User card --}}
                    <div class="border-b border-zinc-200 px-4 py-5 text-center">
                        <flux:avatar circle class="mx-auto size-14" name="{{ auth()->user()->name }}" />
                        <div class="mt-2.5 truncate text-sm font-semibold text-ink">{{ auth()->user()->name }}</div>
                        <div class="truncate text-[11.5px] text-ink-3">{{ auth()->user()->email }}</div>
                    </div>

                    {{-- Nav --}}
                    <div class="p-2">
                        <flux:navlist>

                            <flux:navlist.item icon="home" :href="route('account.dashboard')" :current="request()->routeIs('account.dashboard')" wire:navigate>
                                Dashboard
                            </flux:navlist.item>
                            <flux:navlist.item icon="shopping-bag" :href="route('account.orders.index')" :current="request()->routeIs('account.orders.*')" wire:navigate>
                                Orders
                            </flux:navlist.item>
                            <flux:navlist.item icon="map-pin" :href="route('account.addresses.index')" :current="request()->routeIs('account.addresses.*')" wire:navigate>
                                Address Book
                            </flux:navlist.item>

                            <flux:separator class="my-2" />

                            <flux:navlist.item icon="document-text" :href="route('account.quotes.index')" :current="request()->routeIs('account.quotes.*')" wire:navigate>
                                Quotations
                            </flux:navlist.item>
                            <flux:navlist.item icon="heart" :href="route('wishlist')" :current="request()->routeIs('wishlist')" wire:navigate>
                                Wishlist
                            </flux:navlist.item>
                            <flux:navlist.item icon="star" :href="route('account.reviews')" :current="request()->routeIs('account.reviews')" wire:navigate>
                                Pending Reviews
                            </flux:navlist.item>
                            <flux:navlist.item icon="eye" :href="route('account.recently-viewed')" :current="request()->routeIs('account.recently-viewed')" wire:navigate>
                                Recently Viewed
                            </flux:navlist.item>

                            <flux:separator class="my-2" />

                            <flux:navlist.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.edit', 'security.edit', 'appearance.edit', 'notifications.edit', 'privacy.edit')" wire:navigate>
                                Account Settings
                            </flux:navlist.item>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <flux:navlist.item icon="arrow-right-start-on-rectangle" as="button" type="submit" class="w-full text-red-500! hover:text-red-600! hover:bg-red-50!">
                                    Sign out
                                </flux:navlist.item>
                            </form>

                        </flux:navlist>
                    </div>

                </div>
            </aside>

            {{-- Main content --}}
            <main class="min-w-0 flex-1">
                {{ $slot }}
            </main>

        </div>
    </div>
</x-layouts::storefront>
