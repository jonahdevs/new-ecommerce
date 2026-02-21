<?php
use App\Models\{ShippingRate, ShippingZone, ShippingMethod};
use Livewire\Attributes\{Title, Computed};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Shipping Rates')] class extends Component {
    use WithPagination;

    // Form State
    public ?int $shipping_method_id = null;
    public ?int $shipping_zone_id = null;
    public ?float $min_weight = null;
    public ?float $max_weight = null;
    public ?float $price = null;

    public ?int $estimated_days_min = null;
    public ?int $estimated_days_max = null;
    public ?int $editingId = null;

    // Delete State
    public ?int $rateToDelete = null;
    public ?string $rateDetailsToDelete = null;

    #[Computed]
    public function rates()
    {
        return ShippingRate::with(['method', 'zone'])
            ->orderBy('shipping_zone_id')
            ->paginate(10);
    }

    #[Computed]
    public function methods()
    {
        return ShippingMethod::active()->get();
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::active()->get();
    }

    public function save()
    {
        $data = $this->validate([
            'shipping_method_id' => 'required|exists:shipping_methods,id',
            'shipping_zone_id' => 'required|exists:shipping_zones,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|gt:min_weight',
            'price' => 'required|numeric|min:0',
            'estimated_days_min' => 'nullable|integer|min:0',
            'estimated_days_max' => 'nullable|integer|min:0|gte:estimated_days_min',
        ]);

        ShippingRate::updateOrCreate(['id' => $this->editingId], $data);

        Flux::toast('Shipping rate saved.');
        $this->reset(['shipping_method_id', 'shipping_zone_id', 'price', 'editingId']);
        Flux::modal('rate-modal')->close();
    }

    public function edit($id)
    {
        $rate = ShippingRate::findOrFail($id);
        $this->editingId = $rate->id;
        $this->shipping_method_id = $rate->shipping_method_id;
        $this->shipping_zone_id = $rate->shipping_zone_id;
        $this->min_weight = $rate->min_weight;
        $this->max_weight = $rate->max_weight;
        $this->price = $rate->price;

        Flux::modal('rate-modal')->show();
    }

    public function confirmDelete($id)
    {
        $rate = ShippingRate::with(['method', 'zone'])->findOrFail($id);
        $this->rateToDelete = $id;
        $this->rateDetailsToDelete = "{$rate->zone->name} - {$rate->method->name} ({$rate->min_weight}kg - {$rate->max_weight}kg)";

        Flux::modal('delete-rate')->show();
    }

    public function delete()
    {
        try {
            if ($this->rateToDelete) {
                ShippingRate::destroy($this->rateToDelete);

                Flux::toast(variant: 'success', text: 'Shipping rate deleted successfully.');
                $this->rateToDelete = null;
                $this->rateDetailsToDelete = null;
            }
        } catch (\Throwable $th) {
            \Log::error('Error deleting shipping rate: ' . $th->getMessage(), ['exception' => $th]);
            Flux::toast(variant: 'danger', text: 'Failed to delete shipping rate.');
        }
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl" class="mb-2">Shipping Rate</flux:heading>
            <flux:subheading>Configure pricing based on zones, methods, and weight ranges</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" @click="$flux.modal('rate-modal').show()" class="cursor-pointer">
            Add New Rate
        </flux:button>
    </div>

    <flux:card class="p-0">
        <flux:table :paginate="$this->rates">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Zone</flux:table.column>
                <flux:table.column>Method</flux:table.column>
                <flux:table.column>Weight Range</flux:table.column>
                <flux:table.column>Price</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->rates as $rate)
                    <flux:table.row :key="$rate->id">
                        <flux:table.cell class="font-semibold ps-4!">{{ $rate->zone?->name }}</flux:table.cell>

                        <flux:table.cell>{{ $rate->method?->name }}</flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">
                                {{ $rate->min_weight }}kg - {{ $rate->max_weight }}kg
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="font-semibold">
                            {{ format_currency($rate->price) }}
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square"
                                class="cursor-pointer text-sheffield-blue!" wire:click="edit({{ $rate->id }})"
                                icon-variant="outline" />

                            <flux:button variant="ghost" size="sm" icon="trash" color="danger"
                                class="cursor-pointer text-red-500!" wire:click="confirmDelete({{ $rate->id }})"
                                icon-variant="outline" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="rate-modal" class="md:max-w-xl w-full space-y-6">
        <flux:heading size="lg" class="text-center font-semibold">
            {{ $editingId ? 'Edit Rate' : 'Shipping Rate' }}
        </flux:heading>

        <flux:separator class="-mt-2 mb-8" />

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="shipping_zone_id" label="Shipping Zone">
                    <option value="">Select Zone...</option>
                    @if ($this->zones)
                        @foreach ($this->zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    @endif
                </flux:select>

                <flux:select wire:model="shipping_method_id" label="Method">
                    <option value="">Select Method...</option>

                    @foreach ($this->methods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </flux:select>

                <flux:input type="number" step="0.1" wire:model="min_weight" label="Min Weight (kg)" />
                <flux:input type="number" step="0.1" wire:model="max_weight" label="Max Weight (kg)" />
            </div>

            <flux:input type="number" wire:model="price" label="Price (KES)" icon-leading="banknotes" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input type="number" wire:model="estimated_days_min" label="Min Delivery Time (Days)" />
                <flux:input type="number" wire:model="estimated_days_max" label="Max Delivery Time (Days)" />
            </div>

            <div class="flex pt-4">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Rate</flux:button>
            </div>
        </form>
    </flux:modal>


    {{-- Delete Confirmation Modal --}}
    <flux:modal name="delete-rate" class="md:w-96">
        <flux:heading size="lg" class="mb-2">Delete Shipping Rate</flux:heading>
        <form wire:submit="delete" class="space-y-6">
            <div>
                <flux:subheading>
                    @if ($rateDetailsToDelete)
                        <p class="mt-2">Are you sure you want to delete <strong>{{ $rateDetailsToDelete }}</strong>?
                        </p>
                        <p class="mt-1 text-sm text-red-600">This action cannot be undone.</p>
                    @endif
                </flux:subheading>
            </div>

            <div class="flex gap-2 justify-evenly">
                <flux:button type="button" variant="ghost" wire:click="$dispatch('modal-close', 'delete-rate')"
                    class="w-full">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="danger" class="cursor-pointer w-full">
                    Delete Rate
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
