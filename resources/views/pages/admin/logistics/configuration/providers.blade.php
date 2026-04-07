<?php

use App\Enums\LogisticsProviderStatus;
use App\Models\LogisticsProvider;
use App\Livewire\Forms\Admin\LogisticsProviderForm;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Logistics Providers')] class extends Component {
    use WithPagination;

    public LogisticsProviderForm $form;
    public ?int $deletingId = null;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterType = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    public function updatedSearch(): void
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
    public function providers()
    {
        return LogisticsProvider::query()->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))->when($this->filterType, fn($q) => $q->where('type', $this->filterType))->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))->withCount('shippingMethods')->orderBy('name')->paginate(10);
    }

    #[Computed]
    public function statuses(): array
    {
        return LogisticsProviderStatus::cases();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        Flux::modal('provider-modal')->show();
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->provider;
            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();
            Flux::modal('provider-modal')->close();
            $this->dispatch('notify', title: $isEditing ? 'Provider Updated' : 'Provider Added', variant: 'success', message: $isEditing ? 'Provider updated.' : 'Provider added.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save logistics provider.', [
                'exception' => $e->getMessage(),
                'provider_id' => $this->form->provider?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(LogisticsProvider $provider): void
    {
        $this->form->setProvider($provider);
        Flux::modal('provider-modal')->show();
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
            $provider = LogisticsProvider::findOrFail($this->deletingId);

            if ($provider->shippingMethods()->exists()) {
                $this->dispatch('notify', title: 'Cannot Delete', variant: 'warning', message: 'Cannot delete — this provider has shipping methods attached.');
                Flux::modal('delete-confirmation')->close();
                return;
            }

            $provider->delete();
            $this->deletingId = null;
            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', title: 'Provider Deleted', variant: 'danger', message: 'Provider deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete logistics provider.', [
                'exception' => $e->getMessage(),
                'provider_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Could not delete this provider. It may have dependent records.');
        }
    }
}; ?>

<x-admin.logistics.layout heading="Logistics Providers"
    subheading="Manage the companies that fulfill your deliveries. Start with your own internal operations, add external couriers later.">

    <x-slot:actions>
        <flux:button variant="primary" icon="plus-circle" wire:click="openCreate" class="cursor-pointer">
            Add Provider
        </flux:button>
    </x-slot:actions>

    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="flex flex-col md:flex-row gap-4 px-5 py-3 border-b dark:border-zinc-600">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or code..."
                icon="magnifying-glass" clearable class="max-w-md" />

            <div class="ms-auto flex items-center gap-4">
                <flux:select wire:model.live="filterType" placeholder="All Types" clearable class="md:w-44">
                    <flux:select.option value="internal">Internal</flux:select.option>
                    <flux:select.option value="external">External</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="filterStatus" placeholder="All Statuses" clearable class="md:w-44">
                    @foreach ($this->statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:table :paginate="$this->providers">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Provider</flux:table.column>
                <flux:table.column>Code</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Methods</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->providers as $provider)
                    <flux:table.row :key="$provider->id">
                        <flux:table.cell class="ps-4!">
                            <flux:heading size="sm">{{ $provider->name }}</flux:heading>
                            @if ($provider->description)
                                <flux:subheading class="mt-0.5 max-w-xs truncate">
                                    {{ $provider->description }}
                                </flux:subheading>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">
                                {{ $provider->code }}
                            </code>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge color="{{ $provider->type === 'internal' ? 'blue' : 'purple' }}" variant="flat"
                                size="sm">
                                {{ ucfirst($provider->type) }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ $provider->shipping_methods_count }}</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $status =
                                    $provider->status instanceof \App\Enums\LogisticsProviderStatus
                                        ? $provider->status
                                        : \App\Enums\LogisticsProviderStatus::from($provider->status);
                            @endphp
                            <flux:badge :color="$status->color()" variant="flat" size="sm">
                                {{ $status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer" wire:click="edit({{ $provider->id }})" tooltip="Edit provider" />
                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $provider->id }})" tooltip="Delete provider" />
                        </flux:table.cell>
                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.building-office class="w-10 h-10 opacity-40" />
                                <div>
                                    <flux:heading size="sm">No providers found</flux:heading>
                                    <flux:subheading class="mt-0.5">
                                        @if ($this->search || $this->filterType || $this->filterStatus)
                                            No results match your current filters.
                                        @else
                                            Get started by adding your first logistics provider.
                                        @endif
                                    </flux:subheading>
                                </div>
                                @if ($this->search || $this->filterType || $this->filterStatus)
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="$set('search', ''); $set('filterType', ''); $set('filterStatus', '')">
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
    <flux:modal name="provider-modal" class="md:w-md space-y-6">
        <flux:heading size="lg">{{ $form->provider ? 'Edit Provider' : 'Add New Provider' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="form.name" label="Provider Name" placeholder="e.g. Express Logistics" />
            <flux:input wire:model="form.code" label="Code" placeholder="e.g. provider_code"
                description="Unique identifier. Lowercase, no spaces." />

            <flux:select wire:model="form.type" label="Type">
                <flux:select.option value="internal">Internal — you operate the logistics</flux:select.option>
                <flux:select.option value="external">External — third-party courier</flux:select.option>
            </flux:select>

            <flux:select wire:model="form.status" label="Status">
                @foreach ($this->statuses as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="form.description" label="Description (Optional)"
                placeholder="Brief notes about this provider..." rows="2" />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Provider</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Delete Provider?</flux:heading>
        <flux:subheading>This provider will be permanently removed. Any shipping methods linked to it must be reassigned
            first.</flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Delete</flux:button>
        </div>
    </flux:modal>

</x-admin.logistics.layout>
