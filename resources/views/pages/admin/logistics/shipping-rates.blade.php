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
    public float $min_weight = 0;
    public float $max_weight = 5;
    public float $price = 0;
    public ?int $editingId = null;

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

    public function delete($id)
    {
        ShippingRate::destroy($id);
        Flux::toast(variant: 'danger', text: 'Rate removed.');
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">Shipping Rate Matrix</flux:heading>
            <flux:subheading>Set prices based on zone, method, and weight.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('rate-modal').show()">
            Add New Rate
        </flux:button>
    </div>

    <flux:card class="overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Zone</flux:table.column>
                <flux:table.column>Method</flux:table.column>
                <flux:table.column>Weight Range</flux:table.column>
                <flux:table.column>Price</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->rates as $rate)
                    <flux:table.row :key="$rate->id">
                        <flux:table.cell class="font-semibold">{{ $rate->zone?->name }}</flux:table.cell>
                        <flux:table.cell>{{ $rate->method?->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">
                                {{ $rate->min_weight }}kg - {{ $rate->max_weight }}kg
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono font-bold">
                            KES {{ number_format($rate->price, 2) }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button variant="ghost" size="sm" icon="pencil-square"
                                wire:click="edit({{ $rate->id }})" />
                            <flux:button variant="ghost" size="sm" icon="trash" color="danger"
                                wire:click="delete({{ $rate->id }})" wire:confirm="Remove this rate?" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="rate-modal" class="md:w-120 space-y-6">
        <flux:heading size="lg">{{ $editingId ? 'Edit Rate' : 'Define Shipping Rate' }}</flux:heading>

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
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input type="number" step="0.1" wire:model="min_weight" label="Min Weight (kg)" />
                <flux:input type="number" step="0.1" wire:model="max_weight" label="Max Weight (kg)" />
            </div>

            <flux:input type="number" wire:model="price" label="Price (KES)" icon-leading="banknotes" />

            <div class="flex pt-4">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2">Save Rate</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
