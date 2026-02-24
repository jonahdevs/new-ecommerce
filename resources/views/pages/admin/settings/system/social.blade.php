<?php

use App\Settings\SocialSettings;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Social Media Settings')] class extends Component {
    public string $facebook = '';
    public string $instagram = '';
    public string $twitter = '';
    public string $tiktok = '';
    public string $youtube = '';
    public string $whatsapp = '';
    public string $linkedin = '';

    public function mount(SocialSettings $settings): void
    {
        $this->facebook = $settings->facebook ?? '';
        $this->instagram = $settings->instagram ?? '';
        $this->twitter = $settings->twitter ?? '';
        $this->tiktok = $settings->tiktok ?? '';
        $this->youtube = $settings->youtube ?? '';
        $this->whatsapp = $settings->whatsapp ?? '';
        $this->linkedin = $settings->linkedin ?? '';
    }

    public function rules(): array
    {
        return [
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'youtube' => ['nullable', 'url', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:20'], // phone number not url
            'linkedin' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function save(SocialSettings $settings): void
    {
        $this->validate();

        try {
            $settings->facebook = $this->facebook ?: null;
            $settings->instagram = $this->instagram ?: null;
            $settings->twitter = $this->twitter ?: null;
            $settings->tiktok = $this->tiktok ?: null;
            $settings->youtube = $this->youtube ?: null;
            $settings->whatsapp = $this->whatsapp ?: null;
            $settings->linkedin = $this->linkedin ?: null;
            $settings->save();

            $this->dispatch('notify', variant: 'success', message: 'Social media settings saved.');
        } catch (\Throwable $e) {
            logger()->error('Failed to save social settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    @include('partials.settings-heading')

    <x-pages::admin.settings.layout :heading="__('Social Media')" :subheading="__('Manage your store social media links')">
        <form wire:submit="save" class="space-y-6">

            {{-- Social Links --}}
            <div class="space-y-4">
                <flux:subheading class="font-medium">Social Media Links</flux:subheading>
                <flux:text class="text-xs text-zinc-400">
                    These links will appear in your store footer and contact pages.
                    Leave blank to hide.
                </flux:text>

                {{-- Facebook --}}
                <flux:field>
                    <flux:label>
                        <div class="flex items-center gap-2">
                            <flux:icon.facebook class="size-4 text-blue-600" />
                            Facebook
                        </div>
                    </flux:label>
                    <flux:input wire:model="facebook" placeholder="https://facebook.com/yourpage" icon="link" />
                    <flux:error name="facebook" />
                </flux:field>

                {{-- Instagram --}}
                <flux:field>
                    <flux:label>
                        <div class="flex items-center gap-2">
                            <flux:icon.instagram class="size-4 text-pink-500" />
                            Instagram
                        </div>
                    </flux:label>
                    <flux:input wire:model="instagram" placeholder="https://instagram.com/yourhandle" icon="link" />
                    <flux:error name="instagram" />
                </flux:field>

                {{-- Twitter / X --}}
                <flux:field>
                    <flux:label>
                        <div class="flex items-center gap-2">
                            <flux:icon.twitter class="size-4" />
                            Twitter / X
                        </div>
                    </flux:label>
                    <flux:input wire:model="twitter" placeholder="https://twitter.com/yourhandle" icon="link" />
                    <flux:error name="twitter" />
                </flux:field>

                {{-- TikTok --}}
                <flux:field>
                    <flux:label>
                        <div class="flex items-center gap-2">
                            {{-- <x-icon.tiktok class="size-4" /> --}}
                            TikTok
                        </div>
                    </flux:label>
                    <flux:input wire:model="tiktok" placeholder="https://tiktok.com/@yourhandle" icon="link" />
                    <flux:error name="tiktok" />
                </flux:field>

                {{-- YouTube --}}
                <flux:field>
                    <flux:label>
                        <div class="flex items-center gap-2">
                            <flux:icon.youtube class="size-4 text-red-500" />
                            YouTube
                        </div>
                    </flux:label>
                    <flux:input wire:model="youtube" placeholder="https://youtube.com/@yourchannel" icon="link" />
                    <flux:error name="youtube" />
                </flux:field>

                {{-- LinkedIn --}}
                <flux:field>
                    <flux:label>
                        <div class="flex items-center gap-2">
                            <flux:icon.linkedin class="size-4 text-blue-700" />
                            LinkedIn
                        </div>
                    </flux:label>
                    <flux:input wire:model="linkedin" placeholder="https://linkedin.com/company/yourcompany"
                        icon="link" />
                    <flux:error name="linkedin" />
                </flux:field>

                {{-- WhatsApp --}}
                <flux:field>
                    <flux:label>
                        <div class="flex items-center gap-2">
                            {{-- <flux:icon.whatsapp class="size-4 text-green-500" /> --}}
                            WhatsApp
                        </div>
                    </flux:label>
                    <flux:input wire:model="whatsapp" placeholder="+254 700 000 000" />
                    <flux:description>
                        Phone number only — customers will be taken directly to a WhatsApp chat.
                    </flux:description>
                    <flux:error name="whatsapp" />
                </flux:field>
            </div>

            <flux:separator />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">
                    Save Changes
                </flux:button>
            </div>

        </form>
    </x-pages::admin.settings.layout>
</div>
