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
            ->with(['county', 'area'])
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
    }

    public function openEdit(int $addressId): void
    {
        $address = auth()->user()->addresses()->findOrFail($addressId);
        $this->form->setAddress($address);
        $this->isEditing = true;
        $this->showModal = true;
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
                $this->dispatch('notify', variant: 'success', message: 'Address updated successfully');
            } else {
                $this->form->store();
                $this->dispatch('notify', variant: 'success', message: 'Address saved successfully');
            }
            $this->showModal = false;
            $this->form->reset();
            $this->form->label = 'Home';
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: $th->getMessage() ?: 'Unable to save address');
        }
    }

    public function deleteAddress(int $addressId): void
    {
        $address = auth()->user()->addresses()->where('id', $addressId)->first();

        if (!$address) {
            $this->dispatch('notify', variant: 'danger', message: 'Address not found');
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

            $this->dispatch('notify', variant: 'success', message: 'Address deleted successfully');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to delete address. Please try again.');
        }
    }

    public function setDefaultAddress(int $addressId): void
    {
        try {
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

            $this->dispatch('notify', variant: 'success', message: 'Default address updated');
        } catch (\Throwable $th) {
            $this->dispatch('notify', variant: 'danger', message: 'Failed to update default address.');
        }
    }
};
?>

<div>
    {{-- Address grid --}}
    <div class="bg-white border border-zinc-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-200">
            <div class="flex items-center gap-2 font-serif text-[16px] font-extrabold uppercase tracking-wider">
                <flux:icon.map-pin class="w-4 h-4 text-primary" />
                Saved <em class="text-primary not-italic">Addresses</em>
            </div>
        </div>

        <div class="p-5">
            @if ($this->addresses->isEmpty())
                <div class="py-16 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 bg-zinc-100 rounded-full flex items-center justify-center mb-4">
                        <flux:icon.map-pin class="w-8 h-8 text-zinc-400" />
                    </div>
                    <h4 class="text-base font-semibold text-zinc-900 mb-1">No addresses saved</h4>
                    <p class="text-sm text-zinc-500 mb-5 max-w-xs">Add your shipping addresses to make checkout faster.
                    </p>
                    <flux:button wire:click="openCreate">Add New Address</flux:button>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($this->addresses as $address)
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

                            <div class="text-[14px] font-bold text-zinc-950 mb-1">{{ $address->full_name }}</div>

                            <div class="text-[12px] text-zinc-500 leading-[1.7]">
                                {{ $address->address }}<br>
                                @if ($address->area)
                                    {{ $address->area->name }},
                                @endif{{ $address->county?->name }}<br>
                                {{ $address->phone_number }}
                            </div>

                            <div class="flex flex-wrap gap-3 mt-3.5 pt-3 border-t border-zinc-200">
                                <flux:button variant="ghost" size="xs" wire:click="openEdit({{ $address->id }})">
                                    <flux:icon.pencil-square class="w-3 h-3" />
                                    Edit
                                </flux:button>

                                @if (!$address->is_default)
                                    <flux:button variant="ghost" size="xs"
                                        wire:click="setDefaultAddress({{ $address->id }})">
                                        <flux:icon.check class="w-3 h-3" />
                                        Set Default
                                    </flux:button>

                                    @if ($this->addresses->count() > 1)
                                        <flux:button variant="ghost" size="xs"
                                            wire:click="deleteAddress({{ $address->id }})"
                                            wire:confirm="Are you sure you want to delete this address?"
                                            class="text-red-500! hover:text-red-500!">
                                            <flux:icon.trash class="w-3 h-3" />
                                            Delete
                                        </flux:button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- Add new card --}}
                    <button wire:click="openCreate"
                        class="border-[1.5px] border-dashed border-zinc-200 p-4 flex flex-col items-center justify-center gap-2.5 cursor-pointer min-h-40 transition-all hover:border-primary hover:bg-[#fff8f6] group w-full">
                        <div
                            class="w-10 h-10 rounded-full bg-zinc-100 flex items-center justify-center transition-colors group-hover:bg-[#fff0ea]">
                            <flux:icon.plus
                                class="w-4.5 h-4.5 text-zinc-500 group-hover:text-primary transition-colors" />
                        </div>
                        <div
                            class="text-[12px] font-bold tracking-widest uppercase text-zinc-500 transition-colors group-hover:text-primary">
                            Add New Address
                        </div>
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal --}}

    @if ($showModal)
        <x-ui.modal wire:key="address-form-modal"
            title="{{ $isEditing ? 'EDIT' : 'NEW' }} <em class='text-primary not-italic'>ADDRESS</em>"
            max-width="640px" wire:click.self="closeModal">
            <x-slot:close>
                <button wire:click="closeModal"
                    class="text-zinc-400 hover:text-zinc-950 transition-colors cursor-pointer group">
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
