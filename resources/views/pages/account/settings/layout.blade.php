@props(['heading' => null, 'subheading' => null, 'embedded' => false, 'card' => true])

@php $isAdmin = auth()->user()?->hasRole(['admin', 'staff']) ?? false; @endphp

@if ($embedded && ! $card)
    {{-- The section supplies its own card(s); render the slot bare so we don't
         nest a card inside a card. --}}
    {{ $slot }}
@elseif ($embedded)
    {{-- Rendered as a section inside the admin settings shell: the shell already
         provides tab + section navigation, so render just the section card. --}}
    <flux:card>
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>
        <div class="mt-5">
            {{ $slot }}
        </div>
    </flux:card>
@elseif ($isAdmin)
    <div class="flex items-start max-md:flex-col">
        <div class="me-10 w-full pb-4 md:w-55">
            <flux:navlist aria-label="{{ __('Settings') }}">
                <flux:navlist.item :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                    {{ __('Profile') }}
                </flux:navlist.item>
                <flux:navlist.item :href="route('security.edit')" :current="request()->routeIs('security.edit')" wire:navigate>
                    {{ __('Security') }}
                </flux:navlist.item>
                <flux:navlist.item :href="route('appearance.edit')" :current="request()->routeIs('appearance.edit')" wire:navigate>
                    {{ __('Appearance') }}
                </flux:navlist.item>
                <flux:navlist.item :href="route('privacy.edit')" :current="request()->routeIs('privacy.edit')" wire:navigate>
                    {{ __('Privacy & Data') }}
                </flux:navlist.item>
            </flux:navlist>
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
@else
    <div class="w-full">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>
        <div class="mt-5">
            {{ $slot }}
        </div>
    </div>
@endif
