<x-layouts::storefront :title="$title ?? null">
    <div class="shell py-10">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-start">

            {{-- Sidebar --}}
            <aside class="w-full shrink-0 lg:w-64">
                {{-- User card --}}
                <div class="rounded-md border border-zinc-200 bg-surface-sunken p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold text-white">
                            {{ auth()->user()->initials() }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-ink">{{ auth()->user()->name }}</div>
                            <div class="truncate text-[11.5px] text-ink-3">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                </div>

                {{-- Nav --}}
                <nav class="mt-3">
                    <flux:navlist>
                        <flux:navlist.group heading="My Account">
                            <flux:navlist.item icon="home" :href="route('account.dashboard')" :current="request()->routeIs('account.dashboard')" wire:navigate>
                                Dashboard
                            </flux:navlist.item>
                            <flux:navlist.item icon="shopping-bag" :href="route('account.orders.index')" :current="request()->routeIs('account.orders.*')" wire:navigate>
                                Orders
                            </flux:navlist.item>
                            <flux:navlist.item icon="document-text" :href="route('account.quotes.index')" :current="request()->routeIs('account.quotes.*')" wire:navigate>
                                Quotes & RFQs
                            </flux:navlist.item>
                            <flux:navlist.item icon="map-pin" :href="route('account.addresses.index')" :current="request()->routeIs('account.addresses.*')" wire:navigate>
                                Addresses
                            </flux:navlist.item>
                            <flux:navlist.item icon="heart" :href="route('wishlist')" :current="request()->routeIs('wishlist')" wire:navigate>
                                Wishlist
                            </flux:navlist.item>
                        </flux:navlist.group>

                        <flux:navlist.group heading="Settings">
                            <flux:navlist.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.edit', 'security.edit', 'appearance.edit')" wire:navigate>
                                Account settings
                            </flux:navlist.item>
                        </flux:navlist.group>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <flux:navlist.item icon="arrow-right-start-on-rectangle" as="button" type="submit" class="w-full text-red-500! hover:text-red-600! hover:bg-red-50!">
                                Sign out
                            </flux:navlist.item>
                        </form>
                    </flux:navlist>
                </nav>
            </aside>

            {{-- Main content --}}
            <main class="min-w-0 flex-1">
                {{ $slot }}
            </main>

        </div>
    </div>
</x-layouts::storefront>
