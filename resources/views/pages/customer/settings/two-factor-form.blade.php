<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function enabled(): bool
    {
        return Auth::user()->two_factor_secret !== null;
    }

    public function enableTwoFactor(): void
    {
        Auth::user()->enableTwoFactorAuthentication();
        $this->dispatch('two-factor-enabled');
    }

    public function disableTwoFactor(): void
    {
        Auth::user()->disableTwoFactorAuthentication();
        $this->dispatch('two-factor-disabled');
    }

    public function regenerateRecoveryCodes(): void
    {
        Auth::user()->generateNewRecoveryCodes();
        $this->dispatch('recovery-codes-regenerated');
    }
}; ?>

<div>
    @if ($this->enabled)
        <div class="space-y-4">
            <flux:badge color="green" size="sm">{{ __('Enabled') }}</flux:badge>

            <div class="flex flex-wrap gap-2">
                <flux:button size="sm" wire:click="regenerateRecoveryCodes">
                    {{ __('Regenerate Recovery Codes') }}
                </flux:button>
                <flux:button size="sm" variant="danger" wire:click="disableTwoFactor">
                    {{ __('Disable') }}
                </flux:button>
            </div>

            @if (session('recovery-codes-regenerated'))
                <flux:callout variant="warning" class="mt-4">
                    {{ __('Store these recovery codes in a secure location.') }}
                </flux:callout>
            @endif
        </div>
    @else
        <div class="space-y-4">
            <flux:badge color="zinc" size="sm">{{ __('Not enabled') }}</flux:badge>
            
            <flux:text>
                {{ __('Two-factor authentication adds an extra layer of security by requiring a code from your authenticator app.') }}
            </flux:text>

            <flux:button size="sm" variant="primary" wire:click="enableTwoFactor">
                {{ __('Enable Two-Factor Authentication') }}
            </flux:button>
        </div>
    @endif
</div>
