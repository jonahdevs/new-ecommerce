<?php

use App\Livewire\Forms\Admin\Settings\GeneralSettingsForm;
use App\Settings\GeneralSettings;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Store Info')] class extends Component {
    use WithFileUploads;

    public GeneralSettingsForm $form;

    public function mount(GeneralSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(GeneralSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('Store info saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save store info.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }

    public function removeLogo(GeneralSettings $settings): void
    {
        $this->form->removeLogo($settings);
        $this->dispatch('notify', variant: 'success', title: __('Logo removed'), message: __('Logo removed.'));
    }

    public function removeFavicon(GeneralSettings $settings): void
    {
        $this->form->removeFavicon($settings);
        $this->dispatch('notify', variant: 'success', title: __('Favicon removed'), message: __('Favicon removed.'));
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Store info')" :subheading="__('Your store\'s public identity and contact details')">
        <form wire:submit="save" class="space-y-6">

            {{-- Store Identity --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Store identity') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">

                    {{-- Logo --}}
                    <flux:field>
                        <flux:label>{{ __('Store logo') }}</flux:label>
                        <div class="flex items-center gap-4 bg-zinc-50 dark:bg-zinc-800 rounded p-3">
                            <div
                                class="shrink-0 w-24 h-14 rounded border dark:border-zinc-600 bg-white dark:bg-zinc-900 flex items-center justify-center overflow-hidden">
                                @if ($form->existing_logo)
                                    <img src="{{ Storage::url($form->existing_logo) }}"
                                        class="w-full h-full object-contain p-1" alt="Logo" />
                                @elseif ($form->store_logo)
                                    <img src="{{ $form->store_logo->temporaryUrl() }}"
                                        class="w-full h-full object-contain p-1" alt="Logo preview" />
                                @else
                                    <flux:icon.photo class="w-8 h-8 text-zinc-300 stroke-1!" />
                                @endif
                            </div>
                            <div>
                                <flux:text class="text-xs text-zinc-500">{{ __('Recommended: 200×60px PNG or SVG') }}
                                </flux:text>
                                <div class="flex items-center gap-2 mt-2">
                                    <flux:button as="span" variant="primary" size="xs" class="cursor-pointer"
                                        x-on:click="$refs.logo_input.click()">
                                        {{ $form->existing_logo ? __('Change') : __('Upload') }}
                                    </flux:button>
                                    <input type="file" wire:model="form.store_logo" x-ref="logo_input"
                                        class="sr-only" accept="image/*" />
                                    @if ($form->existing_logo)
                                        <flux:button size="xs" wire:click="removeLogo"
                                            wire:confirm="{{ __('Remove the store logo?') }}" class="cursor-pointer">
                                            {{ __('Remove') }}
                                        </flux:button>
                                    @elseif ($form->store_logo)
                                        <flux:button size="xs" wire:click="$set('form.store_logo', null)"
                                            class="cursor-pointer">
                                            {{ __('Cancel') }}
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <flux:error name="form.store_logo" />
                    </flux:field>

                    {{-- Favicon --}}
                    <flux:field>
                        <flux:label>{{ __('Favicon') }}</flux:label>
                        <div class="flex items-center gap-4 bg-zinc-50 dark:bg-zinc-800 rounded p-3">
                            <div
                                class="shrink-0 w-10 h-10 rounded border dark:border-zinc-600 bg-white dark:bg-zinc-900 flex items-center justify-center overflow-hidden">
                                @if ($form->existing_favicon)
                                    <img src="{{ Storage::url($form->existing_favicon) }}"
                                        class="w-full h-full object-contain" alt="Favicon" />
                                @elseif ($form->store_favicon)
                                    <img src="{{ $form->store_favicon->temporaryUrl() }}"
                                        class="w-full h-full object-contain" alt="Favicon preview" />
                                @else
                                    <flux:icon.photo class="w-6 h-6 text-zinc-300 stroke-1!" />
                                @endif
                            </div>
                            <div>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ __('Recommended: 32×32px ICO or PNG (max 512KB)') }}</flux:text>
                                <div class="flex items-center gap-2 mt-2">
                                    <flux:button as="span" variant="primary" size="xs" class="cursor-pointer"
                                        x-on:click="$refs.favicon_input.click()">
                                        {{ $form->existing_favicon ? __('Change') : __('Upload') }}
                                    </flux:button>
                                    <input type="file" wire:model="form.store_favicon" x-ref="favicon_input"
                                        class="sr-only" accept="image/*" />
                                    @if ($form->existing_favicon)
                                        <flux:button size="xs" wire:click="removeFavicon"
                                            wire:confirm="{{ __('Remove the favicon?') }}" class="cursor-pointer">
                                            {{ __('Remove') }}
                                        </flux:button>
                                    @elseif ($form->store_favicon)
                                        <flux:button size="xs" wire:click="$set('form.store_favicon', null)"
                                            class="cursor-pointer">
                                            {{ __('Cancel') }}
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <flux:error name="form.store_favicon" />
                    </flux:field>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:input label="{{ __('Store name') }}" wire:model="form.store_name" required />
                        <flux:input label="{{ __('Tagline') }}" wire:model="form.store_tagline"
                            placeholder="{{ __('Quality products, trusted service') }}" />
                    </div>
                </div>
            </flux:card>

            {{-- Contact & Address --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Contact & address') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:input label="{{ __('Store email') }}" wire:model="form.store_email" type="email"
                            placeholder="hello@yourstore.com" />
                        <flux:input label="{{ __('Phone number') }}" wire:model="form.store_phone"
                            placeholder="+254 700 000 000" />
                    </div>

                    <flux:input label="{{ __('Address line 1') }}" wire:model="form.store_address"
                        placeholder="{{ __('Street address') }}" />

                    <flux:input label="{{ __('Address line 2') }}" wire:model="form.store_address_line_2"
                        placeholder="{{ __('Suite, floor, building (optional)') }}" />

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <flux:input label="{{ __('City') }}" wire:model="form.store_city" placeholder="Nairobi" />
                        <flux:input label="{{ __('Postal code') }}" wire:model="form.store_postal_code"
                            placeholder="00100" />
                        <flux:input label="{{ __('Country') }}" wire:model="form.store_country" placeholder="Kenya" />
                    </div>

                    <flux:input label="{{ __('State / County') }}" wire:model="form.store_state"
                        placeholder="{{ __('e.g. Nairobi County') }}" />
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
