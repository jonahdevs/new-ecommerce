<?php

use App\Livewire\Forms\Admin\Settings\SocialSettingsForm;
use App\Settings\SocialSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Social Links')] class extends Component {
    public SocialSettingsForm $form;

    public function mount(SocialSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(SocialSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('Social link settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save social settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Social links')" :subheading="__('Your store\'s social media profiles and contact channels')">
        <form wire:submit="save" class="space-y-6">

            {{-- Social Profiles --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Social profiles') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:input label="{{ __('Facebook') }}" wire:model="form.facebook_url"
                            placeholder="https://facebook.com/yourstore" />

                        <flux:input label="{{ __('Instagram') }}" wire:model="form.instagram_url"
                            placeholder="https://instagram.com/yourstore" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:input label="{{ __('X (Twitter)') }}" wire:model="form.twitter_url"
                            placeholder="https://x.com/yourstore" />

                        <flux:input label="{{ __('TikTok') }}" wire:model="form.tiktok_url"
                            placeholder="https://tiktok.com/@yourstore" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:input label="{{ __('YouTube') }}" wire:model="form.youtube_url"
                            placeholder="https://youtube.com/@yourstore" />

                        <flux:input label="{{ __('LinkedIn') }}" wire:model="form.linkedin_url"
                            placeholder="https://linkedin.com/company/yourstore" />
                    </div>
                </div>
            </flux:card>

            {{-- Messaging --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Messaging') }}</flux:heading>
                </div>

                <div class="p-5">
                    <flux:input label="{{ __('WhatsApp number') }}" wire:model="form.whatsapp_number"
                        placeholder="+254700000000"
                        description="{{ __('Used for the WhatsApp chat widget. Include country code.') }}" />
                </div>
            </flux:card>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    {{ __('Save changes') }}
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
