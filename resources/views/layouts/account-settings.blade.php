<x-layouts::storefront :title="$title ?? null">
    <div class="shell pt-4 pb-10">
        @stack('breadcrumbs')
        <div class="mt-6 flex flex-col gap-8 lg:flex-row lg:items-start">

            {{-- Settings sidebar --}}
            <aside class="w-full shrink-0 lg:w-64">
                <nav>
                    <flux:navlist>
                        <flux:navlist.group heading="Settings">
                            <flux:navlist.item icon="user" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                                Profile
                            </flux:navlist.item>
                            <flux:navlist.item icon="shield-check" :href="route('security.edit')" :current="request()->routeIs('security.edit')" wire:navigate>
                                Password & Security
                            </flux:navlist.item>
                            <flux:navlist.item icon="bell" :href="route('notifications.edit')" :current="request()->routeIs('notifications.edit')" wire:navigate>
                                Notifications
                            </flux:navlist.item>
                            <flux:navlist.item icon="lock-closed" :href="route('privacy.edit')" :current="request()->routeIs('privacy.edit')" wire:navigate>
                                Privacy & Data
                            </flux:navlist.item>
                        </flux:navlist.group>

                        <flux:navlist.item icon="arrow-left" :href="route('account.dashboard')" wire:navigate>
                            Back to account
                        </flux:navlist.item>
                    </flux:navlist>
                </nav>
            </aside>

            {{-- Content --}}
            <main class="min-w-0 flex-1">
                {{ $slot }}
            </main>

        </div>
    </div>
</x-layouts::storefront>
