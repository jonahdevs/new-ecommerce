<?php

use App\Settings\ShippingSettings;
use App\Settings\GeneralSettings;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Shipping Settings')] class extends Component {
    public bool $free_shipping_enabled = false;
    public float $free_shipping_threshold = 5000;
    public int $estimated_delivery_days_min = 1;
    public int $estimated_delivery_days_max = 3;
    public string $delivery_estimate_message = '';
    public bool $allow_pickup = true;
    public string $default_weight_unit = 'kg';
    public float $default_packaging_weight = 0.0;

    public function mount(ShippingSettings $settings): void
    {
        $this->free_shipping_enabled = $settings->free_shipping_enabled;
        $this->free_shipping_threshold = $settings->free_shipping_threshold;
        $this->estimated_delivery_days_min = $settings->estimated_delivery_days_min;
        $this->estimated_delivery_days_max = $settings->estimated_delivery_days_max;
        $this->delivery_estimate_message = $settings->delivery_estimate_message;
        $this->allow_pickup = $settings->allow_pickup;
        $this->default_weight_unit = $settings->default_weight_unit;
        $this->default_packaging_weight = $settings->default_packaging_weight;
    }

    public function rules(): array
    {
        return [
            'free_shipping_enabled' => ['boolean'],
            'free_shipping_threshold' => ['required_if:free_shipping_enabled,true', 'numeric', 'min:0'],
            'estimated_delivery_days_min' => ['required', 'integer', 'min:1', 'lt:estimated_delivery_days_max'],
            'estimated_delivery_days_max' => ['required', 'integer', 'min:1', 'gt:estimated_delivery_days_min'],
            'delivery_estimate_message' => ['required', 'string', 'max:255'],
            'allow_pickup' => ['boolean'],
            'default_weight_unit' => ['required', 'in:kg,g'],
            'default_packaging_weight' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function save(ShippingSettings $settings, GeneralSettings $general): void
    {
        $this->validate();

        try {
            $settings->free_shipping_enabled = $this->free_shipping_enabled;
            $settings->free_shipping_threshold = $this->free_shipping_threshold;
            $settings->estimated_delivery_days_min = $this->estimated_delivery_days_min;
            $settings->estimated_delivery_days_max = $this->estimated_delivery_days_max;
            $settings->delivery_estimate_message = $this->delivery_estimate_message;
            $settings->allow_pickup = $this->allow_pickup;
            $settings->default_weight_unit = $this->default_weight_unit;
            $settings->default_packaging_weight = $this->default_packaging_weight;
            $settings->save();

            $this->dispatch('notify', variant: 'success', message: 'Shipping settings saved.');
        } catch (\Throwable $e) {
            logger()->error('Failed to save shipping settings.', ['exception' => $e->getMessage()]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    @include('partials.settings-heading')
    <x-pages::admin.settings.layout :heading="__('Shipping Settings')" :subheading="__('Configure global shipping defaults for your store')">
        <form wire:submit="save" class="space-y-6">

            {{-- Free Shipping --}}
            <div class="space-y-4">
                <flux:subheading class="font-medium">Free Shipping</flux:subheading>

                <div
                    class="flex items-start justify-between gap-4 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <div>
                        <flux:text class="text-sm font-medium">Enable Free Shipping</flux:text>
                        <flux:text class="text-xs text-zinc-400 mt-0.5">
                            Automatically waive shipping fees when order total exceeds the threshold.
                        </flux:text>
                    </div>
                    <flux:switch wire:model.live="free_shipping_enabled" />
                </div>

                @if ($free_shipping_enabled)
                    <flux:field>
                        <flux:label>Free Shipping Threshold</flux:label>
                        <flux:input wire:model="free_shipping_threshold" type="number" min="0" step="100"
                            placeholder="5000" />
                        <flux:description>
                            Orders above this amount qualify for free shipping.
                        </flux:description>
                        <flux:error name="free_shipping_threshold" />
                    </flux:field>
                @endif
            </div>

            <flux:separator />

            {{-- Delivery Estimates --}}
            <div class="space-y-4">
                <div>
                    <flux:subheading class="font-medium">Delivery Estimates</flux:subheading>
                    <flux:text class="text-xs text-zinc-400 mt-1">
                        Shown to customers at checkout and in order confirmation emails.
                    </flux:text>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Min. Delivery Days</flux:label>
                        <flux:input wire:model.live="estimated_delivery_days_min" type="number" min="1" />
                        <flux:error name="estimated_delivery_days_min" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Max. Delivery Days</flux:label>
                        <flux:input wire:model.live="estimated_delivery_days_max" type="number" min="1" />
                        <flux:error name="estimated_delivery_days_max" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Delivery Estimate Message</flux:label>
                    <flux:input wire:model="delivery_estimate_message"
                        placeholder="e.g. Delivered within 1–3 business days" />
                    <flux:description>
                        This message is shown at checkout below the shipping method.
                    </flux:description>
                    <flux:error name="delivery_estimate_message" />
                </flux:field>
            </div>

            <flux:separator />

            {{-- Pickup --}}
            <div class="space-y-4">
                <flux:subheading class="font-medium">Pickup Stations</flux:subheading>

                <div
                    class="flex items-start justify-between gap-4 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <div>
                        <flux:text class="text-sm font-medium">Allow Pickup</flux:text>
                        <flux:text class="text-xs text-zinc-400 mt-0.5">
                            Let customers choose to pick up their order from a pickup station at checkout.
                        </flux:text>
                    </div>
                    <flux:switch wire:model="allow_pickup" />
                </div>
            </div>

            <flux:separator />

            {{-- Weight & Packaging --}}
            <div class="space-y-4">
                <div>
                    <flux:subheading class="font-medium">Weight & Packaging</flux:subheading>
                    <flux:text class="text-xs text-zinc-400 mt-1">
                        Used when calculating shipping rates based on product weight.
                    </flux:text>
                </div>

                <flux:field>
                    <flux:label>Default Weight Unit</flux:label>
                    <flux:select wire:model="default_weight_unit">
                        <flux:select.option value="kg">Kilograms (kg)</flux:select.option>
                        <flux:select.option value="g">Grams (g)</flux:select.option>
                    </flux:select>
                    <flux:error name="default_weight_unit" />
                </flux:field>

                <flux:field>
                    <flux:label>Default Packaging Weight</flux:label>
                    <flux:input wire:model="default_packaging_weight" type="number" min="0" step="0.01"
                        placeholder="0.00" />
                    <flux:description>
                        Added to each order's total weight to account for packaging materials.
                        Enter in {{ $default_weight_unit }}.
                    </flux:description>
                    <flux:error name="default_packaging_weight" />
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
