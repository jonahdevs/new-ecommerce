<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\County;
use App\Models\Area;
use App\Models\CountyBoundary;
use App\Livewire\Forms\CustomerAddressForm;
use Illuminate\Validation\ValidationException;

new #[Layout('layouts.checkout')] class extends Component {
    public CustomerAddressForm $form;

    #[Computed]
    public function counties()
    {
        return County::with('shippingZone')->orderBy('name')->get()->groupBy(fn($county) => $county->shippingZone->is_delivery_available ? ' Available for Delivery' : ' Request a Quote')->sortKeysUsing(fn($a, $b) => str_contains($a, 'Available') ? -1 : 1);
    }

    #[Computed]
    public function areas()
    {
        if (!$this->form->county_id) {
            return collect();
        }

        return Area::where('county_id', $this->form->county_id)->orderBy('name')->get();
    }

    #[Computed]
    public function hasDefaultAddress(): bool
    {
        return auth()->user()->addresses()->where('is_default', true)->exists();
    }

    #[Computed]
    public function mapState(): array
    {
        $county = $this->form->county_id ? County::with('boundary')->find($this->form->county_id) : null;

        $area = $this->form->area_id ? Area::find($this->form->area_id) : null;

        return [
            'pin' => [
                'lat' => $this->form->latitude,
                'lng' => $this->form->longitude,
            ],
            'center' => [
                'lat' => $area?->lat_center ?? ($county?->lat_center ?? -1.2921),
                'lng' => $area?->lng_center ?? ($county?->lng_center ?? 36.8219),
            ],
            'countyName' => $county?->name,
            'boundaryGeojson' => $county?->boundary?->geojson ?? null,
        ];
    }

    public function getMapState(): array
    {
        return $this->mapState();
    }

    public function updatedFormCountyId()
    {
        $this->form->area_id = null;
    }

    public function updatedFormAreaId(): void
    {
        // Triggers mapState recompute — JS picks it up via $wire
    }

    public function save()
    {
        try {
            $this->form->store();

            $this->dispatch('notify', title: 'Address Saved', variant: 'success', message: 'Your delivery address has been saved successfully');

            return $this->redirectRoute('checkout.summary', navigate: true);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to save address');
        }
    }
};
?>

<div>
    {{-- Breadcrumb --}}
    <x-slot:breadcrumbs>
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>Checkout</flux:breadcrumbs.item>

            <flux:breadcrumbs.item :href="route('checkout.addresses.index')" wire:navigate>Addresses
            </flux:breadcrumbs.item>

            <flux:breadcrumbs.item>Create</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </x-slot:breadcrumbs>

    <x-slot:heading>Add New Address</x-slot:heading>

    <flux:card class="p-0 mb-4">
        <div class="px-3 py-2 border-b flex items-center gap-1">
            <flux:icon.check-circle variant="solid" class="size-5" />
            <flux:heading level="3">Customer Address</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-5 p-5">
            @include('pages.checkout.address._form-fields')

            <flux:separator />

            <div class="flex items-center justify-end gap-3">
                <flux:button :href="route('checkout.addresses.index')" wire:navigate class="cursor-pointer">
                    Cancel
                </flux:button>

                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    Save Address
                </flux:button>
            </div>
        </form>
    </flux:card>

    <flux:card class="opacity-70 p-0 mb-4">
        <div class="px-3 py-2 flex items-center gap-1">
            <flux:icon.check-circle variant="solid" class="size-5 text-zinc-600" />
            <flux:heading level="3">Delivery Details</flux:heading>
        </div>
    </flux:card>

    <flux:card class="opacity-70 p-0  mb-4">
        <div class="px-3 py-2 flex items-center gap-1">
            <flux:icon.check-circle variant="solid" class="size-5 text-zinc-600" />
            <flux:heading level="3">Payment Methods</flux:heading>
        </div>
    </flux:card>

    <flux:link :href="route('shop.index')" wire:navigate class="text-xs">Go back & continue shopping
    </flux:link>
</div>
