<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Livewire\Concerns\HasAddressForm;
use App\Models\Address;
use App\Livewire\Forms\CustomerAddressForm;
use Illuminate\Validation\ValidationException;

new #[Layout('layouts.checkout')] class extends Component {
    use HasAddressForm;

    public Address $address;
    public CustomerAddressForm $form;

    public function mount(Address $address)
    {
        abort_if($address->user_id !== auth()->id(), 403);
        $this->form->setAddress($address);
    }

    public function update()
    {
        try {
            $this->form->update();
            $this->dispatch('notify', title: 'Address Updated', variant: 'success', message: 'Your delivery address has been updated successfully');
            return $this->redirectRoute('checkout.summary', navigate: true);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update address');
        }
    }
};
?>

<div>
    <x-slot:breadcrumbs>
        <flux:breadcrumbs class="container mx-auto py-2.5 px-4">
            <flux:breadcrumbs.item :href="route('home')" wire:navigate>
                <flux:icon.home class="w-4 h-4 me-1.5 inline-block" />
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('checkout.summary')" wire:navigate>Checkout</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('checkout.addresses.index')" wire:navigate>Addresses</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </x-slot:breadcrumbs>

    <x-slot:heading>Edit Address</x-slot:heading>

    <flux:card class="p-0 mb-4">
        <div class="px-3 py-2 border-b flex items-center gap-1">
            <flux:icon.check-circle variant="solid" class="size-5 text-zinc-500" />
            <flux:heading level="3">Customer Address</flux:heading>
        </div>

        <form wire:submit="update" class="space-y-5 p-5">
            @include('pages.checkout.address._form-fields')

            <flux:separator />

            <div class="flex items-center justify-end gap-3">
                <flux:button :href="route('checkout.addresses.index')" wire:navigate class="cursor-pointer">Cancel</flux:button>
                <flux:button type="submit" variant="primary" class="cursor-pointer">Save Address</flux:button>
            </div>
        </form>
    </flux:card>

    <flux:card class="p-0 mb-4">
        <div class="px-3 py-2 flex items-center gap-1">
            <flux:icon.check-circle variant="solid" class="size-5 text-zinc-600" />
            <flux:heading level="3">Delivery Details</flux:heading>
        </div>
    </flux:card>

    @if (app(\App\Services\Payment\PaymentService::class)->isCustom())
        <flux:card class="opacity-70 p-0 mb-4">
            <div class="px-3 py-2 flex items-center gap-1">
                <flux:icon.check-circle variant="solid" class="size-5 text-zinc-600" />
                <flux:heading level="3">Payment Methods</flux:heading>
            </div>
        </flux:card>
    @endif

    <flux:link :href="route('shop.index')" wire:navigate class="text-xs">Go back & continue shopping</flux:link>
</div>
