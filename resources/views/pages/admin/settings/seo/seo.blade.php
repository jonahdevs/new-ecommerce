<?php

use App\Livewire\Forms\Admin\Settings\SeoSettingsForm;
use App\Settings\SeoSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('SEO')] class extends Component {
    public SeoSettingsForm $form;

    public function mount(SeoSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(SeoSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('SEO settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save SEO settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('SEO')" :subheading="__('Search engine optimisation and indexing preferences')">
        <form wire:submit="save" class="space-y-6">

            {{-- Default Meta Tags --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Default meta tags') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:input label="{{ __('Meta title') }}" wire:model="form.meta_title"
                        placeholder="{{ __('Your Store Name — Tagline') }}"
                        description="{{ __('Default title used when a page does not define its own. Max 70 characters.') }}" />

                    <flux:textarea label="{{ __('Meta description') }}" wire:model="form.meta_description" rows="2"
                        placeholder="{{ __('A brief description of your store for search engines') }}"
                        description="{{ __('Recommended 150–160 characters.') }}" />

                    <flux:input label="{{ __('Meta keywords') }}" wire:model="form.meta_keywords"
                        placeholder="{{ __('keyword1, keyword2, keyword3') }}"
                        description="{{ __('Comma-separated keywords. Most search engines ignore this, but some still use it.') }}" />
                </div>
            </flux:card>

            {{-- Open Graph & Social --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Open Graph') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:input label="{{ __('Default OG image URL') }}" wire:model="form.og_image"
                        placeholder="https://yourstore.com/images/og-default.jpg"
                        description="{{ __('Used when sharing pages on social media that don\'t have their own image. Recommended 1200×630px.') }}" />
                </div>
            </flux:card>

            {{-- Indexing & Crawling --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Indexing & crawling') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:checkbox wire:model="form.robots_indexing"
                        label="{{ __('Allow search engine indexing') }}"
                        description="{{ __('When disabled, a noindex meta tag is added to all pages. Useful for staging sites.') }}" />

                    <flux:checkbox wire:model="form.sitemap_enabled"
                        label="{{ __('Enable XML sitemap') }}"
                        description="{{ __('Automatically generate a sitemap.xml for search engines') }}" />

                    <flux:separator />

                    <flux:input label="{{ __('Google site verification') }}" wire:model="form.google_site_verification"
                        placeholder="{{ __('Verification code or meta tag content') }}"
                        description="{{ __('The content value from Google Search Console\'s meta tag verification method') }}" />

                    <flux:input label="{{ __('Canonical URL') }}" wire:model="form.canonical_url"
                        placeholder="https://yourstore.com"
                        description="{{ __('Base URL used for canonical tags. Leave blank to use the app URL.') }}" />
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
