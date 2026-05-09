<x-layouts::guest>
    {{-- BREADCRUMB --}}
    <div class="bg-white border-b border-zinc-200 py-3">
        <flux:breadcrumbs class="container mx-auto px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('customer.account') }}" wire:navigate>My Account
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item current>Settings</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="container mx-auto px-4 py-5 lg:py-7 pb-12 lg:pb-15">
        <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-6 items-start">

            <aside
                class="customer-sidebar bg-white border border-zinc-200 sticky top-[calc(var(--nav-h,64px)+16px)] overflow-hidden max-md:static max-md:flex max-md:overflow-x-auto">
                <flux:navlist class="w-full [&_svg]:w-5 [&_svg]:h-5">

                    <flux:navlist.item :href="route('customer.settings.profile')" icon="user" wire:navigate
                        :current="request()->routeIs('customer.settings.profile')">
                        Profile
                    </flux:navlist.item>

                    <flux:navlist.item :href="route('customer.settings.security')" icon="package" wire:navigate
                        :current="request()->routeIs('customer.settings.security')">
                        Password & Security
                    </flux:navlist.item>

                    <flux:navlist.item :href="route('customer.settings.notifications')" wire:navigate icon="book-open">
                        Notifications
                    </flux:navlist.item>

                    <flux:navlist.item :href="route('customer.settings.privacy')" wire:navigate icon="heart">
                        Privacy & Data
                    </flux:navlist.item>

                    <flux:separator class="mt-2" />

                    <flux:navlist.item href="{{ route('customer.account') }}" wire:navigate icon="chevron-left"
                        class="cursor-pointer">Back
                        to Account
                    </flux:navlist.item>

                </flux:navlist>
            </aside>

            {{-- SETTINGS CONTENT --}}
            <main class="page-transition">
                {{ $slot }}
            </main>
        </div>
</x-layouts::guest>
