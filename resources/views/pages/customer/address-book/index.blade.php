<?php

use Livewire\Component;
use Livewire\Attributes\{Layout, Computed};
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.customer')] class extends Component {
    #[Computed]
    public function addresses()
    {
        return auth()->user()->addresses()->orderByDesc('is_default')->oldest()->get();
    }

    public function deleteAddress($addressId)
    {
        $address = auth()->user()->addresses()->where('id', $addressId)->first();

        if (!$address) {
            $this->dispatch('notify', variant: 'danger', message: 'Address not found');
            return;
        }

        try {
            $wasDefault = $address->is_default;

            $address->delete();

            // If deleted address was default, assign oldest remaining address as default
            if ($wasDefault) {
                $oldestAddress = auth()->user()->addresses()->oldest()->first();
                $oldestAddress?->update(['is_default' => true]);
            }

            $this->dispatch('notify', variant: 'success', message: 'Address deleted successfully');
        } catch (\Throwable $th) {
            logger()->error('Failed to delete address', [
                'user_id' => auth()->id(),
                'address_id' => $addressId,
                'error' => $th->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to delete address. Please try again.');
        }
    }

    public function setDefaultAddress($addressId)
    {
        try {
            //code...
            $address = auth()->user()->addresses()->where('id', $addressId)->first();

            if (!$address) {
                $this->dispatch('notify', variant: 'danger', message: 'Address not found');
                return;
            }

            DB::transaction(function () use ($address) {
                auth()
                    ->user()
                    ->addresses()
                    ->update(['is_default' => false]);
                $address->update(['is_default' => true]);
            });

            $this->dispatch('notify', variant: 'success', message: 'Default address updated successfully');
        } catch (\Throwable $th) {
            logger()->error('Failed to set default address', [
                'user_id' => auth()->id(),
                'address_id' => $addressId,
                'error' => $th->getMessage(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Failed to update default address. Please try again.');
        }
    }
};
?>

<div>
    <section class="bg-white border rounded-md">
        <div class="flex justify-between items-center px-4 py-2 border-b">
            <flux:heading level="1" size="lg">Address Book</flux:heading>

            <flux:button size="sm" variant="primary" icon="plus" class="cursor-pointer"
                :href="route('customer.address-book.create')" wire:navigate>
                Add New Address
            </flux:button>
        </div>

        @if ($this->addresses->isEmpty())
            {{-- Empty State --}}
            <div class="p-8 text-center min-h-[48svh] flex flex-col items-center justify-center">
                <div class="mx-auto w-16 h-16 bg-zinc-100 rounded-full flex items-center justify-center mb-4">
                    <flux:icon.map-pin class="w-8 h-8 text-zinc-400" />
                </div>
                <h4 class="text-lg font-medium text-zinc-900 mb-2">No addresses saved</h4>
                <p class="text-sm text-zinc-600 mb-4 max-w-sm mx-auto">
                    Add your shipping and billing addresses to make checkout faster and easier.
                </p>
            </div>
        @else
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($this->addresses as $addressData)
                    <div class="border rounded-lg flex flex-col overflow-hidden">
                        <div @class(['p-4', 'bg-zinc-50' => $addressData->is_default])>
                            <div class="flex items-start justify-between gap-2">
                                <flux:heading class="leading-4">{{ $addressData->full_name }}</flux:heading>
                                @if ($addressData->is_default)
                                    <flux:badge color="green" size="sm">
                                        Default
                                    </flux:badge>
                                @endif
                            </div>

                            <div class="my-3 space-y-1">
                                <flux:text>{{ $addressData->address }}</flux:text>

                                <flux:text>
                                    {{ $addressData->area?->name . ', ' . $addressData->county?->name }}
                                </flux:text>
                                <flux:text>
                                    {{ implode(' / ', array_filter([$addressData->phone_number, $addressData->alternative_phone_number])) }}
                                </flux:text>
                            </div>
                        </div>

                        <div class="px-4 py-2 border-t  flex items-center mt-auto rounded-b-lg">
                            @if (!$addressData->is_default)
                                <flux:button size="sm" variant="ghost" class="cursor-pointer"
                                    wire:click="setDefaultAddress({{ $addressData->id }})">Set as Default
                                </flux:button>
                            @endif

                            <div class="ms-auto flex items-center gap-2">
                                <flux:button icon="pencil" size="xs" variant="ghost" class="cursor-pointer"
                                    :href="route('customer.address-book.edit', $addressData)" wire:navigate>
                                </flux:button>

                                @if ($this->addresses->count() > 1)
                                    <flux:button type="submit" icon="trash" variant="danger" size="xs"
                                        wire:click="deleteAddress({{ $addressData->id }})" class="cursor-pointer">
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
