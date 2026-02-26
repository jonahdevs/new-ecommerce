<?php

use App\Livewire\Forms\Admin\GeneralSettingsForm;
use App\Settings\GeneralSettings;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('General Settings')] class extends Component {
    use WithFileUploads;

    public GeneralSettingsForm $form;

    public function mount(): void
    {
        $this->form->fromSettings(app(GeneralSettings::class));
    }

    public function save(): void
    {
        try {
            $this->form->save(app(GeneralSettings::class));
            $this->dispatch('notify', variant: 'success', message: 'General settings saved.');
        } catch (\Throwable $e) {
            logger()->error('Failed to save general settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function removeImage(string $image): void
    {
        $this->form->removeImage(app(GeneralSettings::class), $image);
    }
}; ?>

<div>
    @include('partials.settings-heading')

    <x-pages::admin.settings.layout :heading="__('General Settings')" :subheading="__('Manage your store identity, contact details and localization')">
        <form wire:submit="save" class="space-y-6">

            {{-- Basic Information --}}
            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Basic Information</flux:heading>
                </div>

                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <flux:input label="Company Name" wire:model="form.company_name" placeholder="e.g. Sheffield Africa" />
                    <flux:input label="Email Address" wire:model="form.email_address"
                        placeholder="e.g. hello@sheffield.com" />
                    <flux:input label="Phone Number" wire:model="form.phone_number" placeholder="+254 700 000 000" />
                </div>

                <flux:separator />

                {{-- Image Uploads --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 p-5">

                    {{-- Logo Light --}}
                    <flux:field>
                        <div class="flex items-center gap-3 bg-zinc-50 rounded-sm p-3 inset-shadow-sm" x-data>
                            <div class="shrink-0 w-20 h-20 rounded border bg-white dark:bg-black">
                                @if ($form->existing_logo_light)
                                    <img src="{{ Storage::url($form->existing_logo_light) }}"
                                        class="w-full h-full object-contain p-1" alt="Light Logo" />
                                @elseif ($form->logo_light)
                                    <img src="{{ $form->logo_light->temporaryUrl() }}"
                                        class="w-full h-full object-contain p-1" alt="Light Logo Preview" />
                                @else
                                    <flux:icon.photo class="w-full h-full p-2 text-zinc-300 stroke-1!" />
                                @endif
                            </div>
                            <div>
                                <flux:heading>Logo (Light)</flux:heading>
                                <flux:text class="text-xs">Recommended: 160px × 50px</flux:text>
                                <div class="flex items-center gap-2 mt-2">
                                    <input type="file" wire:model="form.logo_light" x-ref="logo_light"
                                        class="sr-only" accept="image/*" />
                                    <flux:button type="button" variant="primary" size="xs"
                                        x-on:click="$refs.logo_light.click()" class="cursor-pointer">
                                        {{ $form->existing_logo_light ? 'Change' : 'Upload' }}
                                    </flux:button>
                                    @if ($form->existing_logo_light)
                                        <flux:button size="xs" type="button"
                                            wire:click="removeImage('logo_light')" wire:confirm="Remove light logo?"
                                            class="cursor-pointer">Remove</flux:button>
                                    @elseif ($form->logo_light)
                                        <flux:button size="xs" type="button"
                                            wire:click="$set('form.logo_light', null)" class="cursor-pointer">Cancel
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <flux:error name="form.logo_light" />
                    </flux:field>

                    {{-- Logo Dark --}}
                    <flux:field>
                        <div class="flex items-center gap-3 bg-zinc-50 rounded-sm p-3 inset-shadow-sm" x-data>
                            <div class="shrink-0 w-20 h-20 rounded border bg-white dark:bg-black">
                                @if ($form->existing_logo_dark)
                                    <img src="{{ Storage::url($form->existing_logo_dark) }}"
                                        class="w-full h-full object-contain p-1" alt="Dark Logo" />
                                @elseif ($form->logo_dark)
                                    <img src="{{ $form->logo_dark->temporaryUrl() }}"
                                        class="w-full h-full object-contain p-1" alt="Dark Logo Preview" />
                                @else
                                    <flux:icon.photo class="w-full h-full p-2 text-zinc-300 stroke-1!" />
                                @endif
                            </div>
                            <div>
                                <flux:heading>Logo (Dark)</flux:heading>
                                <flux:text class="text-xs">Recommended: 160px × 50px</flux:text>
                                <div class="flex items-center gap-2 mt-2">
                                    <input type="file" wire:model="form.logo_dark" x-ref="logo_dark" class="sr-only"
                                        accept="image/*" />
                                    <flux:button type="button" variant="primary" size="xs"
                                        x-on:click="$refs.logo_dark.click()" class="cursor-pointer">
                                        {{ $form->existing_logo_dark ? 'Change' : 'Upload' }}
                                    </flux:button>
                                    @if ($form->existing_logo_dark)
                                        <flux:button size="xs" type="button" wire:click="removeImage('logo_dark')"
                                            wire:confirm="Remove dark logo?" class="cursor-pointer">Remove</flux:button>
                                    @elseif ($form->logo_dark)
                                        <flux:button size="xs" type="button"
                                            wire:click="$set('form.logo_dark', null)" class="cursor-pointer">Cancel
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <flux:error name="form.logo_dark" />
                    </flux:field>

                    {{-- Favicon --}}
                    <flux:field>
                        <div class="flex items-center gap-3 bg-zinc-50 rounded-sm p-3 inset-shadow-sm" x-data>
                            <div class="shrink-0 w-20 h-20 rounded border bg-white dark:bg-black">
                                @if ($form->existing_favicon)
                                    <img src="{{ Storage::url($form->existing_favicon) }}"
                                        class="w-full h-full object-contain p-1" alt="Favicon" />
                                @elseif ($form->favicon)
                                    <img src="{{ $form->favicon->temporaryUrl() }}"
                                        class="w-full h-full object-contain p-1" alt="Favicon Preview" />
                                @else
                                    <flux:icon.photo class="w-full h-full p-2 text-zinc-200 stroke-1!" />
                                @endif
                            </div>
                            <div>
                                <flux:heading>Favicon</flux:heading>
                                <flux:text class="text-xs">Recommended: 32px × 32px (max 512KB)</flux:text>
                                <div class="flex items-center gap-2 mt-2">
                                    <input type="file" wire:model="form.favicon" x-ref="favicon" class="sr-only"
                                        accept="image/*" />
                                    <flux:button type="button" variant="primary" size="xs"
                                        x-on:click="$refs.favicon.click()" class="cursor-pointer">
                                        {{ $form->existing_favicon ? 'Change' : 'Upload' }}
                                    </flux:button>
                                    @if ($form->existing_favicon)
                                        <flux:button size="xs" type="button" wire:click="removeImage('favicon')"
                                            wire:confirm="Remove favicon?" class="cursor-pointer">Remove</flux:button>
                                    @elseif ($form->favicon)
                                        <flux:button size="xs" type="button"
                                            wire:click="$set('form.favicon', null)" class="cursor-pointer">Cancel
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <flux:error name="form.favicon" />
                    </flux:field>

                    {{-- Apple Icon --}}
                    <flux:field>
                        <div class="flex items-center gap-3 bg-zinc-50 rounded-sm p-3 inset-shadow-sm" x-data>
                            <div class="shrink-0 w-20 h-20 rounded border bg-white dark:bg-black">
                                @if ($form->existing_apple_icon)
                                    <img src="{{ Storage::url($form->existing_apple_icon) }}"
                                        class="w-full h-full object-contain p-1" alt="Logo Icon" />
                                @elseif ($form->apple_icon)
                                    <img src="{{ $form->apple_icon->temporaryUrl() }}"
                                        class="w-full h-full object-contain p-1" alt="Logo Icon Preview" />
                                @else
                                    <flux:icon.photo class="w-full h-full p-2 text-zinc-300 stroke-1!" />
                                @endif
                            </div>
                            <div>
                                <flux:heading>Apple Icon</flux:heading>
                                <flux:text class="text-xs">Recommended: 50px × 50px</flux:text>
                                <div class="flex items-center gap-2 mt-2">
                                    <input type="file" wire:model="form.apple_icon" x-ref="apple_icon"
                                        class="sr-only" accept="image/*" />
                                    <flux:button type="button" variant="primary" size="xs"
                                        x-on:click="$refs.apple_icon.click()" class="cursor-pointer">
                                        {{ $form->existing_apple_icon ? 'Change' : 'Upload' }}
                                    </flux:button>
                                    @if ($form->existing_apple_icon)
                                        <flux:button size="xs" type="button"
                                            wire:click="removeImage('apple_icon')" wire:confirm="Remove apple icon?"
                                            class="cursor-pointer">Remove</flux:button>
                                    @elseif ($form->apple_icon)
                                        <flux:button size="xs" type="button"
                                            wire:click="$set('form.apple_icon', null)" class="cursor-pointer">Cancel
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <flux:error name="form.apple_icon" />
                    </flux:field>


                </div>
            </flux:card>

            {{-- Address Information --}}
            <flux:card class="p-0">
                <div class="border-b px-3 py-2">
                    <flux:heading>Address Information</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:input label="Address" wire:model="form.address"
                        description:trailing="Used in transactional emails and invoices"
                        placeholder="e.g. 123 Mombasa Road" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <flux:input label="Country" wire:model="form.country" placeholder="e.g. Kenya" />
                        <flux:input label="Town" wire:model="form.town" placeholder="e.g. Nairobi" />
                        <flux:input label="Postal Code" wire:model="form.postal_code" placeholder="e.g. 00100" />
                    </div>
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
