<?php

use App\Livewire\Forms\Admin\Settings\QuotationSettingsForm;
use App\Settings\QuotationSettings;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Quotations')] class extends Component {
    public QuotationSettingsForm $form;

    public function mount(QuotationSettings $settings): void
    {
        $this->form->fromSettings($settings);
    }

    public function save(QuotationSettings $settings): void
    {
        try {
            $this->form->save($settings);
            $this->dispatch('notify', variant: 'success', title: __('Settings saved'), message: __('Quotation settings saved.'));
        } catch (\Throwable $e) {
            logger()->error('Failed to save quotation settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', title: __('Save failed'), message: __('Something went wrong. Please try again.'));
        }
    }
}; ?>

<div>
    <x-pages::admin.settings.layout :heading="__('Quotations')" :subheading="__('Configure quotation requests, validity periods and customer options')">
        <form wire:submit="save" class="space-y-6">

            {{-- General Settings --}}
            <flux:card class="p-0">
                <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                    <flux:heading>{{ __('General settings') }}</flux:heading>
                </div>

                <div class="p-5 space-y-5">
                    <flux:checkbox wire:model.live="form.enabled" label="{{ __('Enable quotation system') }}"
                        description="{{ __('Allow customers to request quotations for products') }}" />

                    @if ($form->enabled)
                        <flux:separator />

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <flux:input label="{{ __('Quote ID prefix') }}" wire:model="form.quote_id_prefix"
                                placeholder="QT-" description="{{ __('e.g. QT-2026-000001') }}" />

                            <flux:input label="{{ __('Admin notification email') }}"
                                wire:model="form.admin_notification_email" type="email"
                                placeholder="{{ __('Leave blank to use store email') }}"
                                description="{{ __('Override email for quote notifications') }}" />
                        </div>
                    @endif
                </div>
            </flux:card>

            @if ($form->enabled)
                {{-- Validity Settings --}}
                <flux:card class="p-0">
                    <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                        <flux:heading>{{ __('Validity period') }}</flux:heading>
                    </div>

                    <div class="p-5 space-y-5">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <flux:input label="{{ __('Default validity (days)') }}"
                                wire:model="form.default_validity_days" type="number" min="1" max="365"
                                description="{{ __('Default days a quote is valid') }}" />

                            <flux:input label="{{ __('Minimum validity (days)') }}"
                                wire:model="form.min_validity_days" type="number" min="1" max="365"
                                description="{{ __('Minimum days admin can set') }}" />

                            <flux:input label="{{ __('Maximum validity (days)') }}"
                                wire:model="form.max_validity_days" type="number" min="1" max="365"
                                description="{{ __('Maximum days admin can set') }}" />
                        </div>

                        <flux:separator />

                        <flux:checkbox wire:model="form.auto_expire_enabled"
                            label="{{ __('Auto-expire overdue quotes') }}"
                            description="{{ __('Automatically mark quotes as expired when validity period ends') }}" />
                    </div>
                </flux:card>

                {{-- Customer Options --}}
                <flux:card class="p-0">
                    <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                        <flux:heading>{{ __('Customer options') }}</flux:heading>
                    </div>

                    <div class="p-5 space-y-5">
                        <flux:checkbox wire:model="form.allow_guest_quotes"
                            label="{{ __('Allow guest quote requests') }}"
                            description="{{ __('Let visitors request quotes without creating an account') }}" />

                        <flux:checkbox wire:model="form.require_phone"
                            label="{{ __('Require phone number') }}"
                            description="{{ __('Make phone number mandatory for quote requests') }}" />
                    </div>
                </flux:card>

                {{-- Quote Document --}}
                <flux:card class="p-0">
                    <div class="border-b border-zinc-200 dark:border-zinc-600 px-4 py-3">
                        <flux:heading>{{ __('Quote document') }}</flux:heading>
                    </div>

                    <div class="p-5 space-y-5">
                        <flux:textarea label="{{ __('Terms & conditions') }}" wire:model="form.quote_terms"
                            rows="4" placeholder="{{ __('Enter terms and conditions to display on quote PDF...') }}"
                            description="{{ __('Shown on the quotation PDF document') }}" />

                        <flux:input label="{{ __('Footer note') }}" wire:model="form.quote_footer_note"
                            placeholder="{{ __('e.g. Prices valid for 7 days from date of issue') }}"
                            description="{{ __('Short note displayed at the bottom of the quote PDF') }}" />
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
