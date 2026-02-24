<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">

        {{-- Account Settings --}}
        <flux:navlist aria-label="{{ __('Account Settings') }}">
            <flux:navlist.group :heading="__('Account')">
                <flux:navlist.item :href="route('profile.edit')" wire:navigate>
                    {{ __('Profile') }}
                </flux:navlist.item>
                <flux:navlist.item :href="route('user-password.edit')" wire:navigate>
                    {{ __('Password') }}
                </flux:navlist.item>
                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                    <flux:navlist.item :href="route('two-factor.show')" wire:navigate>
                        {{ __('Two-Factor Auth') }}
                    </flux:navlist.item>
                @endif
                <flux:navlist.item :href="route('appearance.edit')" wire:navigate>
                    {{ __('Appearance') }}
                </flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>

        {{-- System Settings — staff with permission only --}}
        @can('manage.settings')
            <flux:navlist class="mt-4" aria-label="{{ __('System Settings') }}">
                <flux:navlist.group :heading="__('System')">
                    <flux:navlist.item :href="route('admin.settings.general')" wire:navigate>
                        {{ __('General') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('admin.settings.seo')" wire:navigate>
                        {{ __('SEO') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('admin.settings.mail')" wire:navigate>
                        {{ __('Mail') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('admin.settings.payment')" wire:navigate>
                        {{ __('Payment') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('admin.settings.social')" wire:navigate>
                        {{ __('Social Media') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('admin.settings.shipping')" wire:navigate>
                        {{ __('Shipping') }}
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('admin.settings.maintenance')" wire:navigate>
                        {{ __('Maintenance') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>
        @endcan

    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
