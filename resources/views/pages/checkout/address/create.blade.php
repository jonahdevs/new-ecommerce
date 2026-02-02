<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\County;
use App\Models\Area;

new #[Layout('layouts.guest')] class extends Component {
    public $selectedCounty = null;
    public $selectedArea = null;

    #[Computed]
    public function counties()
    {
        return County::orderBy('name')->get();
    }

    #[Computed]
    public function areas()
    {
        if (!$this->selectedCounty) {
            return collect();
        }

        return Area::where('county_id', $this->selectedCounty)->orderBy('name')->get();
    }
};
?>

<div>
    <div>
        {{-- Breadcrumb --}}
        <div class="bg-zinc-100">
            <flux:breadcrumbs class="container mx-auto py-4 px-4">
                <flux:breadcrumbs.item :href="route('home')" wire:navigate>
                    <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                    Home
                </flux:breadcrumbs.item>

                <flux:breadcrumbs.item :href="route('checkout.summary')">Checkout</flux:breadcrumbs.item>

                <flux:breadcrumbs.item :href="route('checkout.addresses')">Addresses</flux:breadcrumbs.item>

                <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <div class="mx-auto container px-4 py-4 min-h-[80svh]">
            <!-- Checkout Summary Header -->
            <flux:heading level="1" class="text-2xl! font-bold! mb-3">Add New Address</flux:heading>

            <div class="grid grid-cols-4 gap-6">
                <div class="col-span-3 bg-white rounded-sm border">
                    <div class="px-3 py-2 border-b">
                        <flux:heading level="3">Customer Address</flux:heading>
                    </div>
                    <form action="" class="space-y-5 p-5">
                        <div class="grid grid-cols-2 gap-5">
                            {{-- First Name --}}
                            <flux:input name="first_name" :label="__('First Name')" placeholder="John"
                                :value="old('first_name')" />

                            {{-- Last Name --}}
                            <flux:input name="last_name" :label="__('Last Name')" placeholder="Doe"
                                :value="old('last_name')" />

                            {{-- Phone Number --}}
                            <flux:field>
                                <flux:label>{{ __('Phone Number') }}</flux:label>
                                <flux:input.group>
                                    <flux:input.group.prefix>+254</flux:input.group.prefix>
                                    <flux:input name="phone_number" placeholder="Enter Your Phone Number"
                                        :value="old('phone_number')" />
                                </flux:input.group>
                                <flux:error name="phone_number" />
                            </flux:field>

                            {{-- Additional Phone Number --}}
                            <flux:field>
                                <flux:label>{{ __('Additional Phone Number') }}</flux:label>
                                <flux:input.group>
                                    <flux:input.group.prefix>+254</flux:input.group.prefix>
                                    <flux:input name="additional_phone_number"
                                        placeholder="Enter Your Additional Phone Number"
                                        :value="old('additional_phone_number')" />
                                </flux:input.group>
                                <flux:error name="additional_phone_number" />
                            </flux:field>

                            {{-- County --}}
                            <flux:select class="w-full mt-2" wire:model.change="selectedCounty"
                                :label="__('Region/County')">

                                @foreach ($this->counties as $county)
                                    <flux:select.option value="{{ $county->id }}">
                                        {{ $county->name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>

                            {{-- Area --}}
                            <flux:select wire:model="selectedArea" :label="__('City/Area')"
                                :placeholder="$selectedCounty ? 'Select Area' : 'Select a county first'"
                                class="mt-2">
                                @foreach ($this->areas as $area)
                                    <flux:select.option value="{{ $area->id }}">
                                        {{ $area->name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        {{-- Address --}}
                        <flux:input name="address" :label="__('Address')" placeholder="Enter your Address"
                            :value="old('address')" />

                        {{-- Additional Info --}}
                        <flux:textarea name="additional_info" :label="__('Additional Information')"
                            placeholder="Enter Additional Information" :value="old('additional_info')" />

                        {{-- is  Default --}}
                        <flux:field variant="inline">
                            <flux:checkbox wire:model="is_default" />

                            <flux:label>Set as default Address</flux:label>

                            <flux:error name="terms" />
                        </flux:field>

                        <flux:separator />

                        <div class="flex items-center justify-end gap-3">
                            <flux:button :href="route('checkout.addresses')" wire:navigate>Cancel</flux:button>
                            <flux:button type="submit" variant="primary">Save</flux:button>
                        </div>
                    </form>
                </div>

                <div class="col-span-1">
                    <livewire:order-summary />
                </div>
            </div>
        </div>
    </div>
