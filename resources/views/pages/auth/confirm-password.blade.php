@if (auth()->user()->is_staff)
    <x-layouts::app title="Confirm Password">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" icon="home" wire:navigate
                icon-variant="outline" />

            <flux:breadcrumbs.item>Confirm Password</flux:breadcrumbs.item>
            </flux:breadcrumbs.item>

            <div class="min-h-[86svh] grid place-items-center">

                <div class="flex w-full max-w-md mx-auto flex-col gap-6 border p-6 bg-white shadow-sm rounded-sm">

                    <div class="flex flex-col gap-6">
                        <x-auth-header :title="__('Confirm password')" :description="__(
                            'This is a secure area of the application. Please confirm your password before continuing.',
                        )" />

                        <x-auth-session-status class="text-center" :status="session('status')" />

                        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
                            @csrf

                            <flux:input name="password" :label="__('Password')" type="password" required
                                autocomplete="current-password" :placeholder="__('Password')" viewable />

                            <flux:button variant="primary" type="submit" class="w-full"
                                data-test="confirm-password-button">
                                {{ __('Confirm') }}
                            </flux:button>
                        </form>
                    </div>
                </div>
            </div>
    </x-layouts::app>
@else
    <x-layouts::auth title="Confirm Password">
        <div class="flex flex-col gap-6">
            <x-auth-header :title="__('Confirm password')" :description="__('This is a secure area of the application. Please confirm your password before continuing.')" />

            <x-auth-session-status class="text-center" :status="session('status')" />

            <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
                @csrf

                <flux:input name="password" :label="__('Password')" type="password" required
                    autocomplete="current-password" :placeholder="__('Password')" viewable />

                <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                    {{ __('Confirm') }}
                </flux:button>
            </form>
        </div>
    </x-layouts::auth>
@endif
