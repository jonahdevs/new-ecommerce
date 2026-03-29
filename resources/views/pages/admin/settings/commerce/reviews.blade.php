<?php

use App\Livewire\Forms\Admin\Settings\ReviewSettingsForm;
use App\Settings\ReviewSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reviews')] class extends Component {
    public ReviewSettingsForm $form;

    public function mount(ReviewSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(ReviewSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('Review settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save review settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Reviews')" :subheading="__('Customer review permissions and moderation settings')">
        <form wire:submit="save" class="space-y-6">

            {{-- Review Settings --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Review settings') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:checkbox wire:model.live="form.reviews_enabled" label="{{ __('Enable reviews') }}"
                        description="{{ __('Allow customers to leave product reviews') }}" />

                    @if ($form->reviews_enabled)
                        <flux:separator />

                        <flux:checkbox wire:model="form.require_purchase_to_review"
                            label="{{ __('Require purchase to review') }}"
                            description="{{ __('Only verified buyers can submit reviews') }}" />

                        <flux:checkbox wire:model="form.auto_approve_reviews" label="{{ __('Auto-approve reviews') }}"
                            description="{{ __('Publish reviews immediately without manual moderation') }}" />

                        <flux:checkbox wire:model="form.allow_anonymous_reviews"
                            label="{{ __('Allow anonymous reviews') }}"
                            description="{{ __('Let guests leave reviews without an account') }}" />

                        <flux:checkbox wire:model.live="form.allow_review_images"
                            label="{{ __('Allow review images') }}"
                            description="{{ __('Customers can attach photos to their reviews') }}" />
                    @endif
                </div>
            </flux:card>

            {{-- Limits — only when reviews and images are enabled --}}
            @if ($form->reviews_enabled)
                <flux:card class="p-0">
                    <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                        <flux:heading>{{ __('Limits') }}</flux:heading>
                    </div>

                    <div class="p-5 space-y-5">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            @if ($form->allow_review_images)
                                <flux:input label="{{ __('Max images per review') }}"
                                    wire:model="form.max_review_images" type="number" min="1" max="10" />
                            @endif

                            <flux:input label="{{ __('Min review length (characters)') }}"
                                wire:model="form.min_review_length" type="number" min="0" max="1000"
                                description="{{ __('Set to 0 for no minimum') }}" />
                        </div>
                    </div>
                </flux:card>
            @endif

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    {{ __('Save changes') }}
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
