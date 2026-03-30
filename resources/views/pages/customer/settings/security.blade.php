<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.customer')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => $this->passwordRules(),
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('password-updated');
    }
}; ?>

<x-customer-settings-layout heading="Security" subheading="Update your password to keep your account secure">
    <form wire:submit="updatePassword" class="space-y-6">
        <div class="grid grid-cols-1 gap-5 max-w-md">
            <flux:input 
                wire:model="current_password" 
                :label="__('Current Password')" 
                type="password" 
                required 
            />
            <flux:input 
                wire:model="password" 
                :label="__('New Password')" 
                type="password" 
                required 
            />
            <flux:input 
                wire:model="password_confirmation" 
                :label="__('Confirm Password')" 
                type="password" 
                required 
            />
        </div>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Update Password') }}
            </flux:button>

            <x-action-message on="password-updated">
                {{ __('Password updated.') }}
            </x-action-message>
        </div>
    </form>

    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
        <flux:separator class="my-8" />

        <div>
            <flux:heading size="lg">{{ __('Two-Factor Authentication') }}</flux:heading>
            <flux:subheading class="mb-4">{{ __('Add an extra layer of security to your account') }}</flux:subheading>

            <livewire:pages::customer.settings.two-factor-form />
        </div>
    @endif
</x-customer-settings-layout>
