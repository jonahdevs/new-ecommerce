<x-layouts::storefront :title="$title ?? null">
    <div class="mx-auto grid w-full max-w-7xl grid-cols-12 gap-8 px-6 py-8">
        <aside class="col-span-12 lg:col-span-3">
            <flux:navlist>
                <flux:navlist.group :heading="__('My Account')">
                    {{-- TODO: wire `account.*` routes when they're scaffolded --}}
                    <flux:navlist.item icon="home" href="#" :current="request()->routeIs('account.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="shopping-bag" href="#" :current="request()->routeIs('account.orders.*')" wire:navigate>
                        {{ __('Orders') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="map-pin" href="#" :current="request()->routeIs('account.addresses.*')" wire:navigate>
                        {{ __('Addresses') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="heart" href="#" :current="request()->routeIs('account.wishlist')" wire:navigate>
                        {{ __('Wishlist') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group :heading="__('Settings')">
                    <flux:navlist.item icon="user" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                        {{ __('Profile') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="shield-check" :href="route('security.edit')" :current="request()->routeIs('security.edit')" wire:navigate>
                        {{ __('Security') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>
        </aside>

        <main class="col-span-12 lg:col-span-9">
            {{ $slot }}
        </main>
    </div>
</x-layouts::storefront>
