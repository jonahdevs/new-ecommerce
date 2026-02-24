<?php

use App\Settings\MaintenanceSettings;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Maintenance Settings')] class extends Component {
    public bool $enabled = false;
    public string $message = '';
    public string $scheduled_end = '';
    public string $contact_email = '';

    public function mount(MaintenanceSettings $settings): void
    {
        $this->enabled = $settings->enabled;
        $this->message = $settings->message;
        $this->scheduled_end = $settings->scheduled_end ?? '';
        $this->contact_email = $settings->contact_email ?? '';
    }

    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'message' => ['required', 'string', 'max:500'],
            'scheduled_end' => ['nullable', 'date', 'after:now'],
            'contact_email' => ['nullable', 'email'],
        ];
    }

    public function save(MaintenanceSettings $settings): void
    {
        $this->validate();

        try {
            $settings->enabled = $this->enabled;
            $settings->message = $this->message;
            $settings->scheduled_end = $this->scheduled_end ?: null;
            $settings->contact_email = $this->contact_email ?: null;
            $settings->save();

            $this->dispatch('notify', variant: 'success', message: $this->enabled ? 'Maintenance mode enabled. Customers will see the maintenance page.' : 'Maintenance mode disabled. Store is live.');
        } catch (\Throwable $e) {
            logger()->error('Failed to save maintenance settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    @include('partials.settings-heading')

    <x-pages::admin.settings.layout :heading="__('Maintenance')" :subheading="__('Control your store\'s maintenance mode')">
        <form wire:submit="save" class="space-y-6">

            {{-- Toggle --}}
            <div class="space-y-4">

                {{-- Active warning --}}
                @if ($enabled)
                    <div
                        class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4">
                        <flux:icon name="exclamation-triangle"
                            class="size-5 text-red-600 dark:text-red-400 mt-0.5 shrink-0" />
                        <div>
                            <flux:text class="text-sm font-medium text-red-700 dark:text-red-400">
                                Maintenance mode is currently active
                            </flux:text>
                            <flux:text class="text-xs text-red-600 dark:text-red-400 mt-0.5">
                                Customers are seeing the maintenance page. Only staff can access the store.
                            </flux:text>
                        </div>
                    </div>
                @endif

                <div
                    class="flex items-start justify-between gap-4 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <div>
                        <flux:text class="text-sm font-medium">Enable Maintenance Mode</flux:text>
                        <flux:text class="text-xs text-zinc-400 mt-0.5">
                            Customers will see the maintenance page. Staff can still access the store normally.
                        </flux:text>
                    </div>
                    <flux:switch wire:model.live="enabled" />
                </div>
            </div>

            <flux:separator />

            {{-- Message --}}
            <div class="space-y-4">
                <flux:subheading class="font-medium">Maintenance Message</flux:subheading>

                <flux:field>
                    <flux:label>Message</flux:label>
                    <flux:textarea wire:model="message" rows="3"
                        placeholder="We are currently performing scheduled maintenance. We will be back shortly." />
                    <flux:description>Shown to customers on the maintenance page.</flux:description>
                    <flux:error name="message" />
                </flux:field>
            </div>

            <flux:separator />

            {{-- Schedule --}}
            <div class="space-y-4">
                <flux:subheading class="font-medium">Schedule</flux:subheading>

                <flux:field>
                    <flux:label>
                        Expected End Time
                        <flux:badge size="sm" variant="ghost">Optional</flux:badge>
                    </flux:label>
                    <flux:input wire:model="scheduled_end" type="datetime-local" />
                    <flux:description>
                        If set, customers will see when the store is expected to be back online.
                    </flux:description>
                    <flux:error name="scheduled_end" />
                </flux:field>

                <flux:field>
                    <flux:label>
                        Contact Email
                        <flux:badge size="sm" variant="ghost">Optional</flux:badge>
                    </flux:label>
                    <flux:input wire:model="contact_email" type="email" placeholder="hello@sheffield.com" />
                    <flux:description>
                        Customers can reach out to this email during maintenance.
                    </flux:description>
                    <flux:error name="contact_email" />
                </flux:field>
            </div>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" :variant="$enabled ? 'danger' : 'primary'">
                    {{ $enabled ? 'Save & Keep Maintenance Active' : 'Save Changes' }}
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
