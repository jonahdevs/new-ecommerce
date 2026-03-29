<?php

use App\Settings\MaintenanceSettings;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Maintenance Settings')] class extends Component {
    public bool $maintenance_mode = false;
    public string $maintenance_message = '';
    public string $maintenance_allowed_ips = '';
    public string $maintenance_secret = '';

    public function mount(MaintenanceSettings $settings): void
    {
        $this->maintenance_mode = $settings->maintenance_mode;
        $this->maintenance_message = $settings->maintenance_message ?? '';
        $this->maintenance_allowed_ips = $settings->maintenance_allowed_ips ?? '';
        $this->maintenance_secret = $settings->maintenance_secret ?? '';
    }

    public function rules(): array
    {
        return [
            'maintenance_mode' => ['boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
            'maintenance_allowed_ips' => ['nullable', 'string', 'max:500'],
            'maintenance_secret' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function save(MaintenanceSettings $settings): void
    {
        $this->validate();

        try {
            $settings->maintenance_mode = $this->maintenance_mode;
            $settings->maintenance_message = $this->maintenance_message ?: null;
            $settings->maintenance_allowed_ips = $this->maintenance_allowed_ips ?: null;
            $settings->maintenance_secret = $this->maintenance_secret ?: null;
            $settings->save();

            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: $this->maintenance_mode
                ? __('Maintenance mode enabled. Customers will see the maintenance page.')
                : __('Maintenance mode disabled. Store is live.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save maintenance settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Maintenance')" :subheading="__('Control your store\'s maintenance mode')">
        <form wire:submit="save" class="space-y-6">

            {{-- Toggle --}}
            <div class="space-y-4">

                {{-- Active warning --}}
                @if ($maintenance_mode)
                    <div
                        class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4">
                        <flux:icon name="exclamation-triangle"
                            class="size-5 text-red-600 dark:text-red-400 mt-0.5 shrink-0" />
                        <div>
                            <flux:text class="text-sm font-medium text-red-700 dark:text-red-400">
                                {{ __('Maintenance mode is currently active') }}
                            </flux:text>
                            <flux:text class="text-xs text-red-600 dark:text-red-400 mt-0.5">
                                {{ __('Customers are seeing the maintenance page. Only staff can access the store.') }}
                            </flux:text>
                        </div>
                    </div>
                @endif

                <div
                    class="flex items-start justify-between gap-4 rounded-lg border border-zinc-200 dark:border-zinc-600 p-4">
                    <div>
                        <flux:text class="text-sm font-medium">{{ __('Enable Maintenance Mode') }}</flux:text>
                        <flux:text class="text-xs text-zinc-400 mt-0.5">
                            {{ __('Customers will see the maintenance page. Staff can still access the store normally.') }}
                        </flux:text>
                    </div>
                    <flux:switch wire:model.live="maintenance_mode" />
                </div>
            </div>

            <flux:separator />

            {{-- Message --}}
            <flux:card class="p-0">
                <div class="px-3 py-2 border-b">
                    <flux:heading>{{ __('Maintenance message') }}</flux:heading>
                </div>

                <div class="p-5">
                    <flux:textarea label="{{ __('Message') }}" wire:model="maintenance_message" rows="3"
                        description:trailing="{{ __('Shown to customers on the maintenance page.') }}"
                        placeholder="{{ __('We are currently performing scheduled maintenance. We will be back shortly.') }}" />
                </div>
            </flux:card>

            {{-- Access --}}
            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>{{ __('Bypass access') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:input label="{{ __('Allowed IPs') }}" wire:model="maintenance_allowed_ips"
                        placeholder="{{ __('e.g. 192.168.1.1, 10.0.0.1') }}"
                        description="{{ __('Comma-separated IP addresses that can bypass maintenance mode') }}" />

                    <flux:input label="{{ __('Secret bypass token') }}" wire:model="maintenance_secret"
                        placeholder="{{ __('e.g. my-secret-token') }}"
                        description="{{ __('Visitors can bypass maintenance by visiting /my-secret-token') }}" />
                </div>
            </flux:card>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" :variant="$maintenance_mode ? 'danger' : 'primary'" class="cursor-pointer">
                    {{ $maintenance_mode ? __('Save & Keep Maintenance Active') : __('Save Changes') }}
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
