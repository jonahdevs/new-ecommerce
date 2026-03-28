<?php

use App\Livewire\Forms\Admin\Settings\RegionalSettingsForm;
use App\Settings\RegionalSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Regional')] class extends Component {
    public RegionalSettingsForm $form;

    public function mount(RegionalSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(RegionalSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', message: __('Regional settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save regional settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Regional')" :subheading="__('Units used for product weight and dimensions')">
        <form wire:submit="save" class="space-y-6">

            <flux:card class="p-0">
                <div class="border-b px-4 py-3">
                    <flux:heading>{{ __('Measurement units') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <flux:select label="{{ __('Weight unit') }}" wire:model="form.weight_unit"
                            description="{{ __('Used on product pages and shipping calculations') }}">
                            <flux:select.option value="kg">{{ __('kg — Kilogram') }}</flux:select.option>
                            <flux:select.option value="g">{{ __('g — Gram') }}</flux:select.option>
                            <flux:select.option value="lb">{{ __('lb — Pound') }}</flux:select.option>
                            <flux:select.option value="oz">{{ __('oz — Ounce') }}</flux:select.option>
                        </flux:select>

                        <flux:select label="{{ __('Dimension unit') }}" wire:model="form.dimension_unit"
                            description="{{ __('Used for product length, width and height') }}">
                            <flux:select.option value="cm">{{ __('cm — Centimetre') }}</flux:select.option>
                            <flux:select.option value="m">{{ __('m — Metre') }}</flux:select.option>
                            <flux:select.option value="inch">{{ __('inch — Inch') }}</flux:select.option>
                            <flux:select.option value="ft">{{ __('ft — Foot') }}</flux:select.option>
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
