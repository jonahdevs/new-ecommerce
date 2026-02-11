<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\County;
use App\Models\Area;
use App\Livewire\Forms\CustomerAddressForm;
use Illuminate\Validation\ValidationException;

new #[Layout('layouts.guest')] class extends Component {
    public CustomerAddressForm $form;

    #[Computed]
    public function counties()
    {
        return County::withShippingRates()->orderBy('name')->get();
    }

    #[Computed]
    public function areas()
    {
        if (!$this->form->county_id) {
            return collect();
        }

        return Area::where('county_id', $this->form->county_id)->orderBy('name')->get();
    }

    public function updatedFormCountyId()
    {
        $this->form->area_id = '';
    }

    public function save()
    {
        try {
            $this->form->store();
            $this->dispatch('notify', variant: 'success', message: 'Address saved successfully');

            return $this->redirectRoute('checkout.summary', navigate: true);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage());
        }
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

                <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>Checkout</flux:breadcrumbs.item>

                <flux:breadcrumbs.item :href="route('checkout.addresses')" wire:navigate>Addresses
                </flux:breadcrumbs.item>

                <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <div class="mx-auto container px-4 py-4 min-h-[80svh]">
            <!-- Checkout Summary Header -->
            <flux:heading level="1" class="text-2xl! font-bold! mb-3">Add New Address</flux:heading>

            <div class="grid grid-cols-4 gap-6">
                <div class="col-span-3 space-y-4">
                    <div class="col-span-3 bg-white rounded-sm border">
                        <div class="px-3 py-2 border-b flex items-center gap-1">
                            <flux:icon.check-circle variant="solid" class="size-5" />
                            <flux:heading level="3">Customer Address</flux:heading>
                        </div>

                        <form wire:submit="save" class="space-y-5 p-5">
                            <div class="grid grid-cols-2 gap-5">
                                {{-- First Name --}}
                                <flux:input wire:model="form.first_name" :label="__('First Name')" placeholder="John" />

                                {{-- Last Name --}}
                                <flux:input wire:model="form.last_name" :label="__('Last Name')" placeholder="Doe" />

                                {{-- Phone Number --}}
                                <flux:field>
                                    <flux:label>{{ __('Phone Number') }}</flux:label>
                                    <flux:input.group>
                                        <flux:input.group.prefix>+254</flux:input.group.prefix>
                                        <flux:input wire:model="form.phone_number" placeholder="Enter Your Phone Number"
                                            mask="999 999 999" />
                                    </flux:input.group>
                                    <flux:error name="form.phone_number" />
                                </flux:field>

                                {{-- Alternative Phone Number --}}
                                <flux:field>
                                    <flux:label>{{ __('Alternative Phone Number') }}</flux:label>
                                    <flux:input.group>
                                        <flux:input.group.prefix>+254</flux:input.group.prefix>
                                        <flux:input wire:model="form.alternative_phone_number"
                                            placeholder="Enter Your Alternative Phone Number" mask="999 999 999" />
                                    </flux:input.group>
                                    <flux:error name="form.alternative_phone_number" />
                                </flux:field>

                                {{-- County --}}
                                <flux:select wire:model.live="form.county_id" placeholder="Select County..."
                                    :label="__('Region/County')">
                                    @foreach ($this->counties as $county)
                                        <flux:select.option :value="$county->id">
                                            {{ $county->name }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>

                                {{-- Area --}}
                                <flux:select wire:model="form.area_id" :label="__('City/Area')"
                                    :placeholder="$form->county_id ? 'Select Area' : 'Select a county first'">
                                    @foreach ($this->areas as $area)
                                        <flux:select.option :value="$area->id">
                                            {{ $area->name }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            {{-- Address --}}
                            <flux:input wire:model="form.address_text" :label="__('Address')"
                                placeholder="Enter your Address" />

                            {{-- Additional Info --}}
                            <flux:textarea wire:model="form.additional_information"
                                :label="__('Additional Information')" placeholder="Enter Additional Information" />

                            @if (auth()->user()->addresses()->where('is_default', true)->exists())
                                <flux:field variant="inline">
                                    <flux:checkbox wire:model="form.is_default" />
                                    <flux:label>Set as default Address</flux:label>
                                </flux:field>
                            @endif

                            <flux:separator />

                            <div class="flex items-center justify-end gap-3">
                                <flux:button :href="route('checkout.addresses')" wire:navigate class="cursor-pointer">
                                    Cancel
                                </flux:button>

                                <flux:button type="submit" variant="primary" class="cursor-pointer">
                                    Save Address
                                </flux:button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white opacity-70 rounded-sm border">
                        <div class="px-3 py-2 flex items-center gap-1">
                            <flux:icon.check-circle variant="solid" class="size-5 text-zinc-600" />
                            <flux:heading level="3">Delivery Details</flux:heading>
                        </div>
                    </div>

                    <flux:link :href="route('products')" wire:navigate class="text-xs">Go back & continue shopping
                    </flux:link>
                </div>
                <div class="col-span-1">
                    <livewire:order-summary />
                </div>
            </div>
        </div>
    </div>
