<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Coupons | Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public int $perPage = 25;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $code = '';
    public string $type = 'percent';
    public string $value = '';
    public string $min_subtotal = '0';
    public string $max_uses = '';
    public int $max_uses_per_user = 1;
    public bool $is_active = true;
    public string $description = '';
    public string $starts_at = '';
    public string $expires_at = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    #[Computed]
    public function coupons()
    {
        return Coupon::query()
            ->withCount('uses')
            ->when($this->search, fn ($q) => $q->where('code', 'like', '%' . strtoupper($this->search) . '%')
                ->orWhere('description', 'like', '%' . $this->search . '%'))
            ->when($this->filterStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->filterStatus === 'expired', fn ($q) => $q->where('expires_at', '<', now()))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'code', 'value', 'description', 'starts_at', 'expires_at', 'max_uses']);
        $this->type = 'percent';
        $this->min_subtotal = '0';
        $this->max_uses_per_user = 1;
        $this->is_active = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $this->editingId = $id;
        $this->code = $coupon->code;
        $this->type = $coupon->type->value;
        $this->value = (string) $coupon->value;
        $this->min_subtotal = (string) intdiv((int) $coupon->min_subtotal_cents, 100);
        $this->max_uses = $coupon->max_uses !== null ? (string) $coupon->max_uses : '';
        $this->max_uses_per_user = (int) $coupon->max_uses_per_user;
        $this->is_active = (bool) $coupon->is_active;
        $this->description = (string) $coupon->description;
        $this->starts_at = $coupon->starts_at?->format('Y-m-d') ?? '';
        $this->expires_at = $coupon->expires_at?->format('Y-m-d') ?? '';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $isPercent = $this->type === 'percent';
        $data = $this->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($this->editingId), 'regex:/^[A-Z0-9_-]+$/i'],
            'type' => ['required', Rule::in(['fixed', 'percent'])],
            'value' => ['required', 'numeric', 'min:1', $isPercent ? 'max:100' : 'min:1'],
            'min_subtotal' => ['required', 'integer', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'max_uses_per_user' => ['required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $payload = [
            'code' => strtoupper($data['code']),
            'type' => $data['type'],
            'value' => $data['type'] === 'fixed' ? (int) round((float) $data['value'] * 100) : (int) $data['value'],
            'min_subtotal_cents' => (int) $data['min_subtotal'] * 100,
            'max_uses' => $data['max_uses'] ? (int) $data['max_uses'] : null,
            'max_uses_per_user' => (int) $data['max_uses_per_user'],
            'is_active' => (bool) $data['is_active'],
            'description' => $data['description'] ?: null,
            'starts_at' => $data['starts_at'] ?: null,
            'expires_at' => $data['expires_at'] ?: null,
        ];

        if ($this->editingId) {
            Coupon::findOrFail($this->editingId)->update($payload);
            Flux::toast(heading: 'Coupon updated', text: strtoupper($data['code']) . ' has been saved.', variant: 'success');
        } else {
            Coupon::create($payload);
            Flux::toast(heading: 'Coupon created', text: strtoupper($data['code']) . ' is ready to use.', variant: 'success');
        }

        $this->showModal = false;
        unset($this->coupons);
    }

    public function toggleActive(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update(['is_active' => ! $coupon->is_active]);
        unset($this->coupons);
    }

    public function delete(int $id): void
    {
        $coupon = Coupon::withCount('uses')->findOrFail($id);

        if ($coupon->uses_count > 0) {
            Flux::toast(heading: 'Cannot delete', text: $coupon->code . ' has been used ' . $coupon->uses_count . ' time(s).', variant: 'danger');

            return;
        }

        $coupon->delete();
        unset($this->coupons);
        Flux::toast(heading: 'Coupon deleted', text: $coupon->code . ' has been removed.', variant: 'success');
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Marketing</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Coupons</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush
            <flux:heading size="xl">Coupons</flux:heading>
            <flux:subheading>Discount codes customers can apply at checkout.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">New coupon</flux:button>
    </div>

    <flux:card class="mt-6 overflow-hidden p-0">
        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search codes…" icon="magnifying-glass"
                clearable class="max-w-xs" />

            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterStatus" class="w-36">
                    <flux:select.option value="">All statuses</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="inactive">Inactive</flux:select.option>
                    <flux:select.option value="expired">Expired</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-28">
                    <flux:select.option value="25">25 / page</flux:select.option>
                    <flux:select.option value="50">50 / page</flux:select.option>
                    <flux:select.option value="100">100 / page</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Code</flux:table.column>
                <flux:table.column>Discount</flux:table.column>
                <flux:table.column>Usage</flux:table.column>
                <flux:table.column>Validity</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->coupons as $coupon)
                    <flux:table.row :key="$coupon->id">
                        <flux:table.cell>
                            <div class="font-mono text-sm font-semibold tracking-wider text-zinc-800 dark:text-white">
                                {{ $coupon->code }}</div>
                            @if ($coupon->description)
                                <div class="mt-0.5 text-xs text-zinc-400">{{ $coupon->description }}</div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $coupon->valueLabel() }}
                            </div>
                            @if ($coupon->min_subtotal_cents > 0)
                                <div class="mt-0.5 text-xs text-zinc-400">Min {!! money($coupon->min_subtotal_cents) !!}</div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="tabular-nums text-sm text-zinc-600 dark:text-zinc-400">
                            {{ number_format($coupon->uses_count) }}
                            @if ($coupon->max_uses !== null)
                                <span class="text-zinc-400">/ {{ number_format($coupon->max_uses) }}</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="text-xs text-zinc-500">
                            @if ($coupon->starts_at || $coupon->expires_at)
                                <div>
                                    @if ($coupon->starts_at)
                                        From {{ $coupon->starts_at->format('d M Y') }}
                                    @endif
                                    @if ($coupon->expires_at)
                                        <br>Until {{ $coupon->expires_at->format('d M Y') }}
                                    @endif
                                </div>
                            @else
                                <span class="text-zinc-400">No limit</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <button wire:click="toggleActive({{ $coupon->id }})">
                                @php
                                    $expired = $coupon->expires_at && $coupon->expires_at->isPast();
                                @endphp
                                <flux:badge size="sm" inset="top bottom"
                                    :color="$expired ? 'zinc' : ($coupon->is_active ? 'green' : 'zinc')">
                                    {{ $expired ? 'Expired' : ($coupon->is_active ? 'Active' : 'Inactive') }}
                                </flux:badge>
                            </button>
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil-square" icon:variant="outline"
                                    wire:click="openEdit({{ $coupon->id }})" />
                                <flux:button size="xs" variant="ghost" icon="trash-2" icon:variant="outline"
                                    wire:click="delete({{ $coupon->id }})"
                                    wire:confirm="Delete coupon '{{ $coupon->code }}'? This cannot be undone."
                                    class="text-red-500! hover:text-red-600!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-12 text-center text-zinc-400">
                            No coupons found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->coupons->hasPages())
            <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->coupons" />
            </div>
        @endif
    </flux:card>

    {{-- Create / Edit modal --}}
    <flux:modal wire:model.self="showModal" class="md:w-[540px]" :dismissible="false">
        <flux:heading class="uppercase tracking-wide">{{ $editingId ? 'Edit coupon' : 'New coupon' }}</flux:heading>

        <form wire:submit="save" class="mt-5 space-y-4">
            <flux:input wire:model="code" label="Code" placeholder="e.g. SAVE10"
                description="Alphanumeric, dashes and underscores only. Stored uppercase." required />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model.live="type" label="Type">
                    <flux:select.option value="percent">Percentage (%)</flux:select.option>
                    <flux:select.option value="fixed">Fixed amount (KES)</flux:select.option>
                </flux:select>

                <flux:input wire:model="value" type="number" min="1"
                    :max="$type === 'percent' ? 100 : null"
                    :label="$type === 'percent' ? 'Percent off' : 'Amount off (KES)'"
                    :placeholder="$type === 'percent' ? 'e.g. 15' : 'e.g. 500'"
                    required />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="min_subtotal" type="number" min="0"
                    label="Min. order value (KES)"
                    description="0 = no minimum." />

                <flux:input wire:model="max_uses" type="number" min="1"
                    label="Max total uses"
                    description="Leave blank for unlimited." />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="max_uses_per_user" type="number" min="1" max="100"
                    label="Uses per customer" />

                <div class="flex items-end pb-1">
                    <flux:switch wire:model="is_active" label="Active" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="starts_at" type="date" label="Valid from" />
                <flux:input wire:model="expires_at" type="date" label="Expires on" />
            </div>

            <flux:textarea wire:model="description" label="Internal note" rows="2"
                placeholder="e.g. Launch promo — June 2026" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? 'Save changes' : 'Create coupon' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
