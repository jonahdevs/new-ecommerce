<?php

use App\Livewire\Concerns\HasAddressForm;
use App\Livewire\Forms\CustomerAddressForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\{Computed, Layout};
use Livewire\Component;

new #[Layout('layouts.customer')] class extends Component {
    use HasAddressForm;

    public CustomerAddressForm $form;

    public bool $showModal = false;
    public bool $isEditing = false;

    #[Computed]
    public function addresses()
    {
        return auth()
            ->user()
            ->addresses()
            ->with(['county:id,name', 'subCounty:id,name'])
            ->orderByDesc('is_default')
            ->oldest()
            ->get();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        $this->form->label = 'Home';
        $this->isEditing = false;
        $this->showModal = true;
        $this->dispatch('address-modal-opened');
    }

    public function openEdit(int $addressId): void
    {
        $address = auth()->user()->addresses()->findOrFail($addressId);
        $this->form->setAddress($address);
        $this->isEditing = true;
        $this->showModal = true;
        $this->dispatch('address-modal-opened');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->form->reset();
        $this->form->label = 'Home';
    }

    public function save(): void
    {
        try {
            if ($this->isEditing) {
                $this->form->update();
                $this->dispatch('notify', title: 'Address Updated', variant: 'success', message: 'Address updated successfully');
            } else {
                $this->form->store();
                $this->dispatch('notify', title: 'Address Saved', variant: 'success', message: 'Address saved successfully');
            }
            $this->showModal = false;
            $this->form->reset();
            $this->form->label = 'Home';
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: $th->getMessage() ?: 'Unable to save address');
        }
    }

    public function deleteAddress(int $addressId): void
    {
        $address = auth()->user()->addresses()->where('id', $addressId)->first();

        if (!$address) {
            $this->dispatch('notify', title: 'Not Found', variant: 'danger', message: 'Address not found');
            return;
        }

        try {
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                auth()
                    ->user()
                    ->addresses()
                    ->oldest()
                    ->first()
                    ?->update(['is_default' => true]);
            }

            $this->dispatch('notify', title: 'Address Deleted', variant: 'success', message: 'Address deleted successfully');
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Failed to delete address. Please try again.');
        }
    }

    public function setDefaultAddress(int $addressId): void
    {
        try {
            $address = auth()->user()->addresses()->where('id', $addressId)->first();

            if (!$address) {
                $this->dispatch('notify', title: 'Not Found', variant: 'danger', message: 'Address not found');
                return;
            }

            DB::transaction(function () use ($address) {
                auth()
                    ->user()
                    ->addresses()
                    ->update(['is_default' => false]);
                $address->update(['is_default' => true]);
            });

            $this->dispatch('notify', title: 'Default Updated', variant: 'success', message: 'Default address updated');
        } catch (\Throwable $th) {
            $this->dispatch('notify', title: 'Update Failed', variant: 'danger', message: 'Failed to update default address.');
        }
    }
};
?>

<div>
    {{-- Address grid --}}
    <x-customer.card title="Saved" titleEm="Addresses">
        <x-slot:icon>
            <flux:icon.map-pin />
        </x-slot:icon>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($this->addresses as $address)
                <div class="relative border-[1.5px] {{ $address->is_default ? 'border-primary' : 'border-zinc-200 hover:border-zinc-950' }} p-4 transition-colors"
                    wire:key="addr-{{ $address->id }}">
                    @if ($address->is_default)
                        <div class="absolute left-0 top-0 bottom-0 w-0.75 bg-primary rounded-l-sm"></div>
                        <span
                            class="absolute top-3 right-3 text-[9px] font-extrabold tracking-widest uppercase text-primary border border-primary px-2 py-0.5">Default</span>
                    @endif

                    <span
                        class="inline-block text-[9px] font-extrabold tracking-widest uppercase px-2 py-0.5 mb-2 {{ $address->is_default ? 'bg-primary text-white' : 'bg-zinc-950 text-white' }}">
                        {{ $address->label ?? 'Home' }}
                    </span>

                    <div class="text-[14px] font-bold text-on-surface mb-1">{{ $address->full_name }}</div>

                    <div class="text-[12px] text-on-surface-variant leading-[1.7]">
                        {{ $address->address }}<br>
                        @if ($address->subCounty)
                            {{ $address->subCounty->name }},
                        @endif{{ $address->county?->name }}<br>
                        {{ $address->phone_number }}
                    </div>

                    <div class="flex flex-wrap gap-3 mt-3.5 pt-3 border-t border-zinc-200">
                        <flux:button variant="customer-outline" size="customer"
                            wire:click="openEdit({{ $address->id }})" icon="pencil-square">
                            Edit
                        </flux:button>

                        @if (!$address->is_default)
                            <flux:button variant="customer-outline" size="customer"
                                wire:click="setDefaultAddress({{ $address->id }})" icon="check">
                                Set Default
                            </flux:button>

                            @if ($this->addresses->count() > 1)
                                <flux:button variant="customer-danger" size="customer"
                                    wire:click="deleteAddress({{ $address->id }})"
                                    wire:confirm="Are you sure you want to delete this address?" icon="trash">
                                    Delete
                                </flux:button>
                            @endif
                        @endif
                    </div>
                </div>
            @empty
                {{-- Add new card --}}
                <button wire:click="openCreate"
                    class="border-[1.5px] border-dashed border-zinc-200 p-4 flex flex-col items-center justify-center gap-2.5 cursor-pointer min-h-40 transition-all hover:border-primary hover:bg-[#fff8f6] group w-full rounded-sm">
                    <div
                        class="w-10 h-10 rounded-full bg-zinc-100 flex items-center justify-center transition-colors group-hover:bg-[#fff0ea]">
                        <flux:icon.plus class="w-4.5 h-4.5 text-on-surface-variant group-hover:text-primary transition-colors" />
                    </div>
                    <div
                        class="text-[12px] font-bold tracking-widest uppercase text-on-surface-variant transition-colors group-hover:text-primary">
                        Add New Address
                    </div>
                </button>
            @endforelse
        </div>
    </x-customer.card>

    {{-- Modal --}}

    @if ($showModal)
        <x-ui.modal wire:key="address-form-modal"
            title="{{ $isEditing ? 'EDIT' : 'NEW' }} <em class='text-primary not-italic'>ADDRESS</em>"
            max-width="640px" wire:click.self="closeModal">
            <x-slot:close>
                <button wire:click="closeModal"
                    class="text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer group">
                    <flux:icon.x-mark class="w-5 h-5 group-hover:rotate-90 transition-all duration-150 ease-in-out" />
                </button>
            </x-slot:close>

            <form wire:submit="save">
                @include('pages.customer.address-book._form-fields', [
                    'submitLabel' => $isEditing ? 'Update Address' : 'Save Address',
                ])
            </form>
        </x-ui.modal>
    @endif

</div>
