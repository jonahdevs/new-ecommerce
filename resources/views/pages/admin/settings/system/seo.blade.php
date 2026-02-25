<?php

use App\Livewire\Forms\Admin\SeoSettingsForm;
use App\Settings\SeoSettings;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('SEO Settings')] class extends Component {
    use WithFileUploads;

    public SeoSettingsForm $form;

    public function mount(SeoSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(SeoSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', message: 'SEO settings saved.');
        } catch (\Throwable $e) {
            logger()->error('Failed to save SEO settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function removeOgImage(SeoSettings $settings): void
    {
        $this->form->removeOgImage($settings);
    }
}; ?>

<div>
    @include('partials.settings-heading')

    <x-pages::admin.settings.layout :heading="__('SEO Settings')" :subheading="__('Manage how your store appears in search engines and social media')">
        <form wire:submit="save" class="space-y-6">

            {{-- Meta Tags --}}
            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Meta Tags</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:input label="Meta Title" wire:model="form.meta_title" />
                    <flux:input label="Meta Keywords (optional)" wire:model="form.meta_keywords" />
                    <flux:textarea label="Meta Description" wire:model="form.meta_description" rows="3" />
                    <flux:input label="Canonical URL" wire:model="form.canonical_url" />
                </div>
            </flux:card>

            {{-- Open Graph --}}
            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Open Graph</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:input label="OG Title" wire:model.live="form.og_title" />
                    <flux:textarea label="OG Description" wire:model="form.og_description" rows="3" />

                    <flux:field>
                        <flux:label>OG Image</flux:label>

                        <div class="flex items-center gap-4 bg-zinc-50 rounded-sm p-3 inset-shadow-sm">
                            <div class="shrink-0">
                                @if ($form->existing_og_image)
                                    <img src="{{ Storage::url($form->existing_og_image) }}"
                                        class="size-20 object-cover rounded" alt="OG Image" />
                                @elseif ($form->og_image)
                                    <img src="{{ $form->og_image->temporaryUrl() }}"
                                        class="size-20 object-cover rounded" alt="OG Image Preview" />
                                @else
                                    <flux:icon.photo class="size-20 text-inherit! stroke-1!" />
                                @endif
                            </div>

                            <div>
                                <flux:heading>OG Image</flux:heading>
                                <flux:text class="text-xs">Recommended size: 1200px × 630px (max 2MB)</flux:text>

                                <div class="flex items-center gap-2 mt-2">
                                    <label>
                                        <flux:button as="span" variant="primary" size="xs"
                                            class="cursor-pointer">
                                            {{ $form->existing_og_image ? 'Change' : 'Upload' }}
                                        </flux:button>
                                        <input type="file" wire:model="form.og_image" class="sr-only"
                                            accept="image/*" />
                                    </label>

                                    @if ($form->existing_og_image)
                                        <flux:button size="xs" wire:click="removeOgImage"
                                            wire:confirm="Remove the current OG image?" class="cursor-pointer">
                                            Remove
                                        </flux:button>
                                    @elseif ($form->og_image)
                                        <flux:button size="xs" wire:click="$set('form.og_image', null)"
                                            class="cursor-pointer">
                                            Cancel
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <flux:error name="form.og_image" />
                    </flux:field>
                </div>
            </flux:card>

            {{-- Analytics --}}
            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Analytics & Verification</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:input label="Google Analytics ID" wire:model="form.google_analytics_id"
                        placeholder="G-XXXXXXXXXX" />
                    <flux:input label="Google Tag Manager ID" wire:model="form.google_tag_manager_id"
                        placeholder="GTM-XXXXXXX" />
                    <flux:input label="Google Site Verification" wire:model="form.google_site_verification" />
                </div>
            </flux:card>

            {{-- Indexing --}}
            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Indexing</flux:heading>
                </div>

                <div class="p-5">
                    <flux:checkbox wire:model="form.indexing_enabled" label="Allow search engines to index this site" />
                </div>
            </flux:card>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    Save Changes
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
