<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Livewire\Concerns\HasAddressForm;
use App\Models\Address;
use App\Livewire\Forms\CustomerAddressForm;
use Illuminate\Validation\ValidationException;

new #[Layout('layouts.customer')] class extends Component {
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
            return $this->redirectRoute('customer.address-book.index', navigate: true);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to update address');
        }
    }
};
?>

<div>
    <flux:card class="p-0">
        <div class="flex items-center gap-3 px-3 py-2 border-b">
            <flux:button size="xs" icon="arrow-long-left" variant="ghost" class="cursor-pointer"
                :href="route('customer.address-book.index')" wire:navigate />
            <flux:heading size="lg">Edit Address</flux:heading>
        </div>

        <form wire:submit="update" class="space-y-5 p-5">
            @include('pages.checkout.address._form-fields')

            <flux:separator />

            <div class="flex items-center justify-end gap-3">
                <flux:button :href="route('customer.address-book.index')" wire:navigate class="cursor-pointer">Cancel</flux:button>
                <flux:button type="submit" variant="primary" class="cursor-pointer">Save Address</flux:button>
            </div>
        </form>
    </flux:card>
</div>
