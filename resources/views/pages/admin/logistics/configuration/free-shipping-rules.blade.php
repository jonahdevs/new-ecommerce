<?php

use App\Enums\FreeShippingRuleStatus;
use App\Models\FreeShippingRule;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Livewire\Forms\Admin\FreeShippingRuleForm;
use Livewire\Attributes\{Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Component;
use Flux\Flux;

new #[Title('Free Shipping Rules')] class extends Component {
    use WithPagination;

    public FreeShippingRuleForm $form;
    public ?int $deletingId = null;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function rules()
    {
        return FreeShippingRule::with(['shippingZone', 'shippingMethod'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderByRaw("FIELD(status, 'active', 'scheduled', 'inactive', 'expired')")
            ->orderBy('starts_at', 'desc')
            ->paginate(10);
    }

    #[Computed]
    public function zones()
    {
        return ShippingZone::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function methods()
    {
        return ShippingMethod::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function statuses(): array
    {
        return FreeShippingRuleStatus::cases();
    }

    public function openCreate(): void
    {
        $this->form->reset();
        Flux::modal('rule-modal')->show();
    }

    public function save(): void
    {
        try {
            $isEditing = (bool) $this->form->rule;
            $isEditing ? $this->form->update() : $this->form->store();

            $this->form->reset();
            Flux::modal('rule-modal')->close();
            $this->dispatch('notify', title: $isEditing ? 'Rule Updated' : 'Rule Created', variant: 'success', message: $isEditing ? 'Rule updated.' : 'Rule created.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('Failed to save free shipping rule.', [
                'exception' => $e->getMessage(),
                'rule_id' => $this->form->rule?->id,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Save Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }

    public function edit(FreeShippingRule $rule): void
    {
        $this->form->setRule($rule);
        Flux::modal('rule-modal')->show();
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
            FreeShippingRule::destroy($this->deletingId);
            $this->deletingId = null;
            Flux::modal('delete-confirmation')->close();
            $this->dispatch('notify', title: 'Rule Deleted', variant: 'danger', message: 'Rule deleted.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete free shipping rule.', [
                'exception' => $e->getMessage(),
                'rule_id' => $this->deletingId,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Could not delete this rule.');
        }
    }
}; ?>

<x-admin.logistics.layout heading="Free Shipping Rules"
    subheading="Promotional thresholds that waive shipping at checkout. Scope by zone and method, and schedule with start and end dates.">

    <x-slot:actions>
        <flux:button variant="primary" icon="plus-circle" wire:click="openCreate" class="cursor-pointer">
            Add Rule
        </flux:button>
    </x-slot:actions>

    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">
        {{-- Filters --}}
        <div class="flex flex-col md:flex-row gap-4 px-5 py-3 border-b dark:border-zinc-600">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by rule name..."
                icon="magnifying-glass" clearable class="max-w-md" />

            <div class="ms-auto flex items-center gap-5">
                <flux:select wire:model.live="filterStatus" placeholder="All Statuses" clearable class="md:w-44">
                    @foreach ($this->statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
        <flux:table :paginate="$this->rules">
            <flux:table.columns>
                <flux:table.column class="ps-4!">Rule</flux:table.column>
                <flux:table.column>Min Order</flux:table.column>
                <flux:table.column>Scope</flux:table.column>
                <flux:table.column>Schedule</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="pe-4!">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->rules as $rule)
                    <flux:table.row :key="$rule->id">
                        <flux:table.cell class="ps-4!">
                            <flux:heading size="sm">{{ $rule->name }}</flux:heading>
                            @if ($rule->max_weight)
                                <flux:subheading class="mt-0.5">Max weight: {{ $rule->max_weight }} kg</flux:subheading>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:heading size="sm">{{ format_currency($rule->min_order_amount) }}</flux:heading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="space-y-1">
                                @if ($rule->shippingZone)
                                    <flux:badge color="blue" variant="flat" size="sm">
                                        {{ $rule->shippingZone->name }}</flux:badge>
                                @else
                                    <flux:subheading>All zones</flux:subheading>
                                @endif
                                @if ($rule->shippingMethod)
                                    <flux:badge color="purple" variant="flat" size="sm" class="block">
                                        {{ $rule->shippingMethod->name }}</flux:badge>
                                @else
                                    <flux:subheading class="block">All methods</flux:subheading>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="space-y-0.5">
                                @if ($rule->starts_at)
                                    <flux:subheading>
                                        From: {{ $rule->starts_at->format('d M Y') }}
                                    </flux:subheading>
                                @endif
                                @if ($rule->ends_at)
                                    <flux:subheading>
                                        To: {{ $rule->ends_at->format('d M Y') }}
                                    </flux:subheading>
                                @endif
                                @if (!$rule->starts_at && !$rule->ends_at)
                                    <flux:subheading>No schedule</flux:subheading>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $status =
                                    $rule->status instanceof \App\Enums\FreeShippingRuleStatus
                                        ? $rule->status
                                        : \App\Enums\FreeShippingRuleStatus::from($rule->status);
                            @endphp
                            <flux:badge :color="$status->color()" variant="flat" size="sm">
                                {{ $status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end" class="pe-4!">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" icon-variant="outline"
                                class="cursor-pointer" wire:click="edit({{ $rule->id }})" tooltip="Edit rule" />
                            <flux:button variant="ghost" size="sm" icon="trash" icon-variant="outline"
                                color="red" class="cursor-pointer text-red-500!"
                                wire:click="confirmDelete({{ $rule->id }})" tooltip="Delete rule" />
                        </flux:table.cell>
                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400">
                                <flux:icon.gift class="w-10 h-10 opacity-40" />
                                <div>
                                    <flux:heading size="sm">No free shipping rules</flux:heading>
                                    <flux:subheading class="mt-0.5">
                                        @if ($this->search || $this->filterStatus)
                                            No results match your current filters.
                                        @else
                                            Create a rule to offer free shipping above a spend threshold.
                                        @endif
                                    </flux:subheading>
                                </div>
                                @if ($this->search || $this->filterStatus)
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="$set('search', ''); $set('filterStatus', '')">
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
    <flux:modal name="rule-modal" class="md:w-lg space-y-6">
        <flux:heading size="lg">{{ $form->rule ? 'Edit Rule' : 'Add Free Shipping Rule' }}</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="form.name" label="Rule Name" placeholder="e.g. Christmas Promo 2025" />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.min_order_amount" label="Min Order Amount ({{ get_currency_symbol() }})"
                    type="number" min="0" step="0.01" placeholder="e.g. 5000" />
                <flux:input wire:model="form.max_weight" label="Max Weight (Kg, Optional)" type="number" min="0"
                    step="0.01" placeholder="No weight ceiling" />
            </div>

            {{-- Scope --}}
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 space-y-3">
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Scope (leave blank to apply everywhere)
                </p>
                <flux:select wire:model="form.shipping_zone_id" label="Zone" clearable placeholder="All zones">
                    @foreach ($this->zones as $zone)
                        <flux:select.option value="{{ $zone->id }}">{{ $zone->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="form.shipping_method_id" label="Method" clearable placeholder="All methods">
                    @foreach ($this->methods as $method)
                        <flux:select.option value="{{ $method->id }}">{{ $method->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Schedule --}}
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 space-y-3">
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Schedule (Optional)</p>
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="form.starts_at" label="Starts At" type="datetime-local" />
                    <flux:input wire:model="form.ends_at" label="Ends At" type="datetime-local" />
                </div>
            </div>

            <flux:select wire:model="form.status" label="Status">
                @foreach ($this->statuses as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" class="cursor-pointer">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2 cursor-pointer">Save Rule</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Confirmation --}}
    <flux:modal name="delete-confirmation" class="md:w-88 space-y-6">
        <flux:heading size="lg" class="mb-2">Delete Rule?</flux:heading>
        <flux:subheading>This free shipping rule will be permanently removed.</flux:subheading>
        <div class="flex gap-3">
            <flux:modal.close class="flex-1">
                <flux:button variant="ghost" class="w-full cursor-pointer">Cancel</flux:button>
            </flux:modal.close>
            <flux:button wire:click="delete" variant="danger" class="flex-1 cursor-pointer">Delete</flux:button>
        </div>
    </flux:modal>

</x-admin.logistics.layout>
