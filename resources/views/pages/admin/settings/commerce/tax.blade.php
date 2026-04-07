<?php

use App\Livewire\Forms\Admin\Settings\TaxSettingsForm;
use App\Models\TaxClass;
use App\Settings\TaxSettings;
use Livewire\Attributes\{Title, Computed};
use Livewire\Component;

new #[Title('Tax')] class extends Component {
    public TaxSettingsForm $form;

    public function mount(TaxSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    #[Computed]
    public function taxClasses()
    {
        return TaxClass::orderBy('name')->get();
    }

    public function save(TaxSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('Tax settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save tax settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Tax')" :subheading="__('VAT/GST configuration and shipping tax rules')">
        <form wire:submit="save" class="space-y-6">

            {{-- Enable Tax --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('Tax configuration') }}</flux:heading>
                </div>

                <div class="p-5">
                    <flux:checkbox wire:model.live="form.tax_enabled" label="{{ __('Enable tax') }}"
                        description="{{ __('Apply tax to product prices at checkout') }}" />
                </div>
            </flux:card>

            {{-- Tax Details — only shown when tax is enabled --}}
            @if ($form->tax_enabled)
                <flux:card class="p-0">
                    <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                        <flux:heading>{{ __('Tax details') }}</flux:heading>
                    </div>

                    <div class="p-5 space-y-5">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <flux:input label="{{ __('Tax name') }}" wire:model="form.tax_name" placeholder="VAT"
                                description="{{ __('Shown on invoices and checkout e.g. VAT, GST, Sales Tax') }}" />

                            <flux:select wire:model="form.default_tax_class_id"
                                label="{{ __('Default tax class') }}"
                                placeholder="{{ __('None — no tax applied by default') }}"
                                description="{{ __('Applied to products with no tax class assigned') }}"
                                clearable>
                                @foreach ($this->taxClasses as $taxClass)
                                    <flux:select.option value="{{ $taxClass->id }}">
                                        {{ $taxClass->name }} — {{ $taxClass->rateLabel() }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <flux:select label="{{ __('Tax type') }}" wire:model="form.tax_type">
                                <flux:select.option value="exclusive">
                                    {{ __('Exclusive — added on top of price') }}
                                </flux:select.option>
                                <flux:select.option value="inclusive">
                                    {{ __('Inclusive — already included in price') }}
                                </flux:select.option>
                            </flux:select>

                            <flux:input label="{{ __('Tax registration number') }}"
                                wire:model="form.tax_registration_number"
                                placeholder="{{ __('PIN / VAT registration number') }}"
                                description="{{ __('Printed on invoices and receipts') }}" />
                        </div>

                        <flux:separator />

                        <flux:checkbox wire:model="form.taxable_shipping"
                            label="{{ __('Apply tax to shipping charges') }}"
                            description="{{ __('The configured tax rate will also be applied to shipping costs') }}" />
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
