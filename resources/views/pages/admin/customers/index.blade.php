<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Customers — Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public int $perPage = 15;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers()
    {
        return User::query()
            ->whereDoesntHave('roles')
            ->withCount('orders')
            ->withSum('orders', 'total_cents')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate($this->perPage);
    }
}; ?>

@php
    $kes = fn ($cents) => 'KES&nbsp;'.number_format(intdiv((int) $cents, 100), 0, '.', ',');
@endphp

<div>
    <div class="flex items-center justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Customers</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl" class="mt-2">Customers</flux:heading>
            <flux:subheading>Everyone who has registered a storefront account.</flux:subheading>
        </div>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or email…"
                icon="magnifying-glass"
                clearable
                class="max-w-xs" />

            <flux:select wire:model.live="perPage" class="w-28">
                <flux:select.option value="15">15 / page</flux:select.option>
                <flux:select.option value="25">25 / page</flux:select.option>
                <flux:select.option value="50">50 / page</flux:select.option>
            </flux:select>
        </div>

        <flux:table
            container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column align="end">Orders</flux:table.column>
                <flux:table.column align="end">Total spent</flux:table.column>
                <flux:table.column align="end">Joined</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->customers as $customer)
                    <flux:table.row :key="$customer->id" class="cursor-pointer"
                        wire:click="$navigate('{{ route('admin.customers.show', $customer) }}')">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$customer->name" :initials="$customer->initials()" size="sm" />
                                <div>
                                    <div class="font-medium text-sm dark:text-white">{{ $customer->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $customer->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="tabular-nums text-zinc-500">{{ $customer->orders_count }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-medium tabular-nums">{!! $kes($customer->orders_sum_total_cents) !!}</flux:table.cell>
                        <flux:table.cell align="end" class="text-sm text-zinc-500">{{ $customer->created_at->format('M j, Y') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="py-12 text-center text-zinc-400">
                            No customers found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->customers->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->customers" />
            </div>
        @endif
    </flux:card>
</div>
