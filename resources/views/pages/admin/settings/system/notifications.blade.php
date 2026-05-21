<?php

use App\Settings\NotificationSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Notification Channels')] class extends Component {
    public bool $email_notifications_enabled = true;
    public bool $sms_notifications_enabled = false;
    public bool $push_notifications_enabled = false;
    public string $admin_notification_email = '';

    public function mount(NotificationSettings $settings): void
    {
        $this->email_notifications_enabled = $settings->email_notifications_enabled;
        $this->sms_notifications_enabled = $settings->sms_notifications_enabled;
        $this->push_notifications_enabled = $settings->push_notifications_enabled;
        $this->admin_notification_email = $settings->admin_notification_email ?? '';
    }

    public function rules(): array
    {
        return [
            'email_notifications_enabled' => ['boolean'],
            'sms_notifications_enabled' => ['boolean'],
            'push_notifications_enabled' => ['boolean'],
            'admin_notification_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function save(NotificationSettings $settings): void
    {
        $this->validate();

        $settings->email_notifications_enabled = $this->email_notifications_enabled;
        $settings->sms_notifications_enabled = $this->sms_notifications_enabled;
        $settings->push_notifications_enabled = $this->push_notifications_enabled;
        $settings->admin_notification_email = $this->admin_notification_email ?: null;

        $settings->save();

        $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('Notification channel settings saved.'));
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Notification Channels')" :subheading="__('Control which channels are active system-wide for sending notifications')">
        <form wire:submit="save" class="space-y-6">

            {{-- ── CHANNELS ── --}}
            <flux:card class="p-0">

                <div class="border-b border-zinc-200 dark:border-zinc-600 px-5 py-3 flex items-center gap-2">
                    <flux:icon.signal class="size-4 text-zinc-500" />
                    <flux:heading>{{ __('Channels') }}</flux:heading>
                </div>

                {{-- Email --}}
                <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="text-[13px] font-semibold text-zinc-800 dark:text-zinc-100 mb-0.5">{{ __('Email') }}</div>
                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ __('Send notifications via email to admins and customers') }}</div>
                    </div>
                    <label class="relative inline-block w-9 h-5 cursor-pointer">
                        <input type="checkbox" class="peer sr-only" wire:model.live="email_notifications_enabled">
                        <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full peer-checked:bg-primary transition-colors"></div>
                        <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                    </label>
                </div>

                {{-- SMS --}}
                <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-zinc-200 dark:border-zinc-700 opacity-60">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-0.5">
                            <div class="text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">{{ __('SMS') }}</div>
                            <flux:badge size="sm" color="zinc">{{ __('Coming soon') }}</flux:badge>
                        </div>
                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ __('Send SMS notifications via a connected provider') }}</div>
                    </div>
                    <label class="relative inline-block w-9 h-5 cursor-not-allowed">
                        <input type="checkbox" class="peer sr-only" wire:model.live="sms_notifications_enabled" disabled>
                        <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full peer-checked:bg-primary transition-colors"></div>
                        <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                    </label>
                </div>

                {{-- Push --}}
                <div class="flex items-center justify-between gap-4 px-5 py-4 opacity-60">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-0.5">
                            <div class="text-[13px] font-semibold text-zinc-800 dark:text-zinc-100">{{ __('Push') }}</div>
                            <flux:badge size="sm" color="zinc">{{ __('Coming soon') }}</flux:badge>
                        </div>
                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ __('Send browser and mobile push notifications') }}</div>
                    </div>
                    <label class="relative inline-block w-9 h-5 cursor-not-allowed">
                        <input type="checkbox" class="peer sr-only" wire:model.live="push_notifications_enabled" disabled>
                        <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-600 rounded-full peer-checked:bg-primary transition-colors"></div>
                        <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                    </label>
                </div>

            </flux:card>

            {{-- ── RECIPIENTS ── --}}
            <flux:card class="p-0">

                <div class="border-b border-zinc-200 dark:border-zinc-600 px-5 py-3 flex items-center gap-2">
                    <flux:icon.at-symbol class="size-4 text-zinc-500" />
                    <flux:heading>{{ __('Recipients') }}</flux:heading>
                </div>

                <div class="px-5 py-4">
                    <flux:field>
                        <flux:label>{{ __('Admin notification email') }}</flux:label>
                        <flux:description>{{ __('All admin alert emails will be sent to this address. Leave blank to use the default admin account email.') }}</flux:description>
                        <flux:input wire:model="admin_notification_email" type="email" placeholder="alerts@example.com" />
                        <flux:error name="admin_notification_email" />
                    </flux:field>
                </div>

            </flux:card>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    <span wire:loading.remove wire:target="save">{{ __('Save changes') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
