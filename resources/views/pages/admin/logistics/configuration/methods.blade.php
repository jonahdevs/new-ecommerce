<?php

use App\Enums\ShippingMethodStatus;
use App\Models\ShippingMethod;
use App\Models\LogisticsProvider;
use App\Livewire\Forms\Admin\ShippingMethodForm;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Shipping Methods')] class extends Component {
    use WithPagination;

    public ShippingMethodForm $form;
    public ?int $deletingId = null;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterProvider = '';

    #[Url(history: true)]
    public string $filterType = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedFilterProvider(): void
    {
        $this->resetPage();
    }
    public function updatedFilterType(): void
    {
        $this->resetPage();
    }
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function methods()
    {
        return ShippingMethod::with('logisticsProvider')->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))->when($this->filterProvider, fn($q) => $q->where('logistics_provider_id', $this->filterProvider))->when($this->filterType, fn($q) => $q->where('type', $this->filterType))->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))->orderBy('sort_order')->orderBy('name')->paginate(10);
    }

    #[Computed]
    public function providers()
    {
        return LogisticsProvider::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function statuses(): array
    {
        return ShippingMethodStatus::cases();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        Flux::modal('method-modal')->show();
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->method;
            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();
            Flux::modal('method-modal')->close();
            $this->dispatch('notify', variant: 'success', message: $isEditing ? 'Method updated.' : 'Method added.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save shipping method.', [
                'exception' => $e->getMessage(),
                'method_id' => $this->form->method?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(ShippingMethod $method): void
    {
        $this->form->setMethod($method);
        Flux::modal('method-modal')->show();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('delete-confirmation')->show();
    }

    public function delete(): void
    {
        if (!$this->deletingId) {
            return;
        }

        try {
            $method = ShippingMethod::findOrFail($this->deletingId);

            if ($method->shippingRates()->exists() || $method->vehicleRates()->exists()) {
                $this->dispatch('notify', variant: 'warning', message: 'Cannot delete — this method has rates attached. Deprecate it instead.');
                Flux::modal('delete-confirmation')->close();
                return;
            }

            $method->delete();
            $this->deletingId = null;
            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', variant: 'danger', message: 'Method deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete shipping method.', [
                'exception' => $e->getMessage(),
                'method_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', variant: 'danger', message: 'Could not delete this method. It may have dependent records.');
        }
    }
}; ?>

<x-admin.logistics.layout heading="Shipping Methods"
    subheading="The delivery options shown to customers at checkout. Each method is powered by a pricing engine — flat, distance, or pickup station.">

    <div class="flex items-center justify-end mb-5">
        <flux:button variant="primary" icon="plus-circle" wire:click="openCreate" class="cursor-pointer">
            Add Method
        </flux:button>
    </div>

    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="flex flex-col md:flex-row gap-4 px-5 py-3 border-b dark:border-zinc-600">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or code..."
                icon="magnifying-glass" clearable class="max-w-sm" />

            <div class="ms-auto flex items-center gap-5">
                <flux:select wire:model.live="filterProvider" placeholder="All Providers" clearable class="md:w-48">
                    @foreach ($this->providers as $provider)
                        <flux:select.option value="{{ $provider->id }}">{{ $provider->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterType" placeholder="All Types" clearable class="md:w-44">
                    <flux:select.option value="flat">Flat Rate</flux:select.option>
                    <flux:select.option value="distance">Distance (On-Demand)</flux:select.option>
                    <flux:select.option value="pus">Pickup Station</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="filterStatus" placeholder="All Statuses" clearable class="md:w-44">
                    @foreach ($this->statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:table :paginate="$this->methods">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Method</flux:table.column>
                <flux:table.column>Provider</flux:table.column>
                <flux:table.column>Pricing Engine</flux:table.column>
                <flux:table.column>Delivery Time</flux:table.column>
                <flux:table.column>Returns</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->methods as $method)
                    <flux:table.row :key="$method->id">
                        <flux:table.cell class="ps-4!">
                            <div class="font-semibold">{{ $method->name }}</div>
                            <code class="text-xs text-zinc-400">{{ $method->code }}</code>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm">{{ $method->logisticsProvider->name }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge
                                color="{{ match ($method->type) {
                                    'flat' => 'blue',
                                    'distance' => 'purple',
                                    'pus' => 'orange',
                                } }}"
                                variant="flat" size="sm">
                                {{ match ($method->type) {
                                    'flat' => 'Flat Rate',
                                    'distance' => 'Distance',
                                    'pus' => 'Pickup Station',
                                } }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-sm capitalize">{{ $method->delivery_time_unit }}</span>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($method->supports_returns)
                                <flux:icon.check-circle variant="outline" class="w-4 h-4 text-green-500" />
                            @else
                                <flux:icon.x-circle variant="outline" class="w-4 h-4 text-zinc-300" />
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @php $status = $method->status instanceof \App\Enums\ShippingMethodStatus ? $method->status : \App\Enums\ShippingMethodStatus::from($method->status); @endphp
                            <flux:badge :color="$status->color()" variant="flat" size="sm">
                                {{ $status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer text-brand-secondary!" wire:click="edit({{ $method->id }})" />
                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $method->id }})" />
                        </flux:table.cell>
                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.truck class="w-10 h-10 opacity-40" />
                                <div>
                                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">No shipping methods
                                        found</p>
                                    <p class="text-xs mt-0.5">
                                        @if ($this->search || $this->filterProvider || $this->filterType || $this->filterStatus)
                                            No results match your current filters.
                                        @else
                                            Add a shipping method to start offering delivery options at checkout.
                                        @endif
                                    </p>
                                </div>
                                @if ($this->search || $this->filterProvider || $this->filterType || $this->filterStatus)
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="$set('search', ''); $set('filterProvider', ''); $set('filterType', ''); $set('filterStatus', '')">
                                        Clear filters
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Create / Edit Modal --}}
    <flux:modal name="method-modal" class="md:w-lg space-y-6">
        <flux:heading size="lg">{{ $form->method ? 'Edit Method' : 'Add New Method' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.name" label="Method Name" placeholder="e.g. Same-Day Delivery"
                    class="col-span-2" />

                <flux:input wire:model="form.code" label="Code" placeholder="e.g. same_day"
                    description="Unique. Lowercase, underscores." />

                <flux:input wire:model="form.sort_order" label="Sort Order" type="number" min="0"
                    description="Lower numbers appear first." />
            </div>

            <flux:select wire:model="form.logistics_provider_id" label="Logistics Provider"
                placeholder="Select a provider...">
                @foreach ($this->providers as $provider)
                    <flux:select.option value="{{ $provider->id }}">{{ $provider->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="form.type" label="Pricing Engine">
                    <flux:select.option value="flat">Flat Rate (weight × zone)</flux:select.option>
                    <flux:select.option value="distance">Distance (vehicle × km)</flux:select.option>
                    <flux:select.option value="pus">Pickup Station (flat + surcharge)</flux:select.option>
                </flux:select>

                <flux:select wire:model="form.delivery_time_unit" label="Delivery Time Unit">
                    <flux:select.option value="hours">Hours (e.g. Same-Day)</flux:select.option>
                    <flux:select.option value="days">Days (e.g. Standard)</flux:select.option>
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="form.status" label="Status">
                    @foreach ($this->statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="form.icon" label="Icon (Optional)" placeholder="e.g. truck"
                    description="Heroicon name for UI display." />
            </div>

            <flux:checkbox wire:model="form.supports_returns" label="Supports return shipments" />

            <flux:textarea wire:model="form.description" label="Description (Optional)"
                placeholder="Describe this delivery option to customers..." rows="2" />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Method</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Delete Method?</flux:heading>
        <flux:subheading>Methods with existing rates cannot be deleted. Set the status to <strong>Deprecated</strong>
            instead to hide it from checkout while preserving historical records.</flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Delete</flux:button>
        </div>
    </flux:modal>

    <style>
        [data-flux-pagination] {
            padding-inline: 1rem;
            padding-bottom: 1rem;
        }
    </style>

</x-admin.logistics.layout>
