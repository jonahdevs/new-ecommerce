<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\County;
use App\Models\Area;
use App\Models\Address;
use App\Livewire\Forms\CustomerAddressForm;
use Illuminate\Validation\ValidationException;

new #[Layout('layouts.guest')] class extends Component {
    public Address $address;
    public CustomerAddressForm $form;

    public function mount(Address $address)
    {
        $this->form->setAddress($address);
    }

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

    public function update()
    {
        try {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', message: 'Address updated successfully');
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

                <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>
                    Checkout
                </flux:breadcrumbs.item>

                <flux:breadcrumbs.item :href="route('checkout.addresses')" wire:navigate>
                    Addresses
                </flux:breadcrumbs.item>

                <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <div class="mx-auto container px-4 py-4 min-h-[80svh]">
            <!-- Checkout Summary Header -->
            <flux:heading level="1" class="text-2xl! font-bold! mb-3">Edit Address</flux:heading>

            <div class="grid grid-cols-4 gap-6">
                <div class="col-span-3 space-y-3">
                    <div class="bg-white rounded-sm border">
                        <div class="px-3 py-2 border-b flex items-center gap-1">
                            <flux:icon.check-circle variant="solid" class="size-5 text-zinc-500" />
                            <flux:heading level="3">Customer Address</flux:heading>
                        </div>

                        <form wire:submit="update" class="space-y-5 p-5">
                            @include('pages.checkout.address._form-fields')

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
