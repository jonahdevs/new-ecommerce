<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.customer')] class extends Component {
    public bool $newsletter_subscribed = false;

    public function mount(): void
    {
        $this->newsletter_subscribed = Auth::user()->newsletter_subscribed ?? false;
    }

    public function save(): void
    {
        Auth::user()->update([
            'newsletter_subscribed' => $this->newsletter_subscribed,
        ]);

        $this->dispatch('preferences-updated');
    }
}; ?>

<x-customer-settings-layout heading="Preferences" subheading="Manage your communication preferences">
    <form wire:submit="save" class="space-y-6">
        <div class="space-y-4">
            <flux:heading size="base">{{ __('Newsletter') }}</flux:heading>
            
            <flux:checkbox 
                wire:model="newsletter_subscribed" 
                :label="__('Subscribe to newsletter')"
                :description="__('Receive updates about new products, promotions, and special offers.')"
            />
        </div>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Save Preferences') }}
            </flux:button>

            <x-action-message on="preferences-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</x-customer-settings-layout>
