<?php

use App\Livewire\Forms\Admin\Settings\LocalizationSettingsForm;
use App\Settings\LocalizationSettings;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Localization')] class extends Component {
    public LocalizationSettingsForm $form;

    public function mount(LocalizationSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(LocalizationSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', message: __('Localization settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save localization settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: __('Something went wrong. Please try again.'));
        }
    }

    // Live currency preview — recomputes whenever any form field changes
    #[Computed]
    public function preview(): string
    {
        return $this->form->preview();
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Localization')" :subheading="__('Currency, number formatting and locale preferences')">
        <form wire:submit="save" class="space-y-6">

            {{-- Currency --}}
            <flux:card class="p-0">
                <div class="border-b px-4 py-3">
                    <flux:heading>{{ __('Currency') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:input label="{{ __('Currency code') }}" wire:model.live="form.currency" placeholder="KES"
                            description="{{ __('ISO 4217 code e.g. KES, USD, GBP') }}" />
                        <flux:input label="{{ __('Currency symbol') }}" wire:model.live="form.currency_symbol"
                            placeholder="Ksh" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:select label="{{ __('Currency position') }}" wire:model.live="form.currency_position">
                            <flux:select.option value="before">{{ __('Before — Ksh100') }}</flux:select.option>
                            <flux:select.option value="before_space">{{ __('Before with space — Ksh 100') }}
                            </flux:select.option>
                            <flux:select.option value="after">{{ __('After — 100Ksh') }}</flux:select.option>
                            <flux:select.option value="after_space">{{ __('After with space — 100 Ksh') }}
                            </flux:select.option>
                        </flux:select>

                        <flux:select label="{{ __('Decimal places') }}" wire:model.live="form.decimal_places">
                            <flux:select.option value="2">{{ __('2 — 1,250.00') }}</flux:select.option>
                            <flux:select.option value="0">{{ __('0 — 1,250') }}</flux:select.option>
                        </flux:select>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:select label="{{ __('Decimal separator') }}" wire:model.live="form.decimal_separator">
                            <flux:select.option value=".">{{ __('Period (1,250.00)') }}</flux:select.option>
                            <flux:select.option value=",">{{ __('Comma (1.250,00)') }}</flux:select.option>
                        </flux:select>

                        <flux:select label="{{ __('Thousands separator') }}"
                            wire:model.live="form.thousands_separator">
                            <flux:select.option value=",">{{ __('Comma (1,250)') }}</flux:select.option>
                            <flux:select.option value=".">{{ __('Period (1.250)') }}</flux:select.option>
                            <flux:select.option value=" ">{{ __('Space (1 250)') }}</flux:select.option>
                        </flux:select>
                    </div>

                    {{-- Live preview --}}
                    <div class="flex items-center gap-3 rounded-lg bg-zinc-50 dark:bg-zinc-800 px-4 py-3">
                        <flux:icon.eye class="w-4 h-4 text-zinc-400 shrink-0" />
                        <flux:text class="text-sm text-zinc-500">{{ __('Preview:') }}</flux:text>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100" wire:key="preview">
                            {{ $this->preview }}
                        </span>
                    </div>
                </div>
            </flux:card>

            {{-- Locale --}}
            <flux:card class="p-0">
                <div class="border-b px-4 py-3">
                    <flux:heading>{{ __('Locale') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:select label="{{ __('Timezone') }}" wire:model="form.timezone">
                            <flux:select.option value="Africa/Nairobi">Africa/Nairobi (EAT +3)</flux:select.option>
                            <flux:select.option value="Africa/Lagos">Africa/Lagos (WAT +1)</flux:select.option>
                            <flux:select.option value="Africa/Johannesburg">Africa/Johannesburg (SAST +2)
                            </flux:select.option>
                            <flux:select.option value="UTC">UTC</flux:select.option>
                            <flux:select.option value="Europe/London">Europe/London (GMT)</flux:select.option>
                            <flux:select.option value="America/New_York">America/New_York (EST)</flux:select.option>
                        </flux:select>

                        <flux:select label="{{ __('Date format') }}" wire:model="form.date_format">
                            <flux:select.option value="d/m/Y">28/03/2026 (d/m/Y)</flux:select.option>
                            <flux:select.option value="m/d/Y">03/28/2026 (m/d/Y)</flux:select.option>
                            <flux:select.option value="Y-m-d">2026-03-28 (Y-m-d)</flux:select.option>
                            <flux:select.option value="d M Y">28 Mar 2026 (d M Y)</flux:select.option>
                        </flux:select>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:select label="{{ __('Time format') }}" wire:model="form.time_format">
                            <flux:select.option value="12">{{ __('12-hour (2:30 PM)') }}</flux:select.option>
                            <flux:select.option value="24">{{ __('24-hour (14:30)') }}</flux:select.option>
                        </flux:select>

                        <flux:select label="{{ __('Language') }}" wire:model="form.language">
                            <flux:select.option value="en">English</flux:select.option>
                            <flux:select.option value="sw">Swahili</flux:select.option>
                        </flux:select>
                    </div>
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
