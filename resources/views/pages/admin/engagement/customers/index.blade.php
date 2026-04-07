<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Computed};

new #[Title('Customers')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedStatus(): void
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
        return User::customer()
            ->withCount('orders')
            ->when(
                $this->search,
                fn($q) => $q->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
                }),
            )
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => User::customer()->count(),
            'active' => User::customer()->where('status', 'active')->count(),
            'new_this_month' => User::customer()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'banned' => User::customer()->where('status', 'banned')->count(),
        ];
    }

    public function delete(int $id): void
    {
        try {
            User::findOrFail($id)->delete();
            $this->dispatch('notify', title: 'Customer Deleted', variant: 'success', message: 'Customer deleted successfully.');
        } catch (\Throwable $e) {
            logger()->error('Failed to delete customer.', [
                'customer_id' => $id,
                'user_id' => auth()->id(),
                'exception_message' => $e->getMessage(),
            ]);
            $this->dispatch('notify', title: 'Delete Failed', variant: 'danger', message: 'Something went wrong. Please try again.');
        }
    }
}; ?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Customers</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Customers</flux:heading>
            <flux:subheading>Manage your customer accounts</flux:subheading>
        </div>

        <flux:button icon="plus-circle" variant="primary" :href="route('admin.customers.create')" wire:navigate>
            Add Customer
        </flux:button>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6 mb-6">
        <flux:card class="p-4 border-l-4 border-l-blue-500 rounded-l-none! dark:border-l-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Total Customers</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!" x-data="countUp({ to: {{ $this->stats['total'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">All time</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.users class="size-5 text-blue-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-emerald-500 rounded-l-none! dark:border-l-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Active</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!" x-data="countUp({ to: {{ $this->stats['active'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">Active accounts</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.user-check class="size-5 text-emerald-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-violet-500 rounded-l-none! dark:border-l-violet-500">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">New This Month</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!" x-data="countUp({ to: {{ $this->stats['new_this_month'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">{{ now()->format('F Y') }}</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-violet-50 dark:bg-violet-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.user-plus class="size-5 text-violet-500" />
                </div>
            </div>
        </flux:card>

        <flux:card class="p-4 border-l-4 border-l-red-500 rounded-l-none! dark:border-l-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Banned</flux:subheading>
                    <flux:heading size="xl" class="text-2xl! font-bold!" x-data="countUp({ to: {{ $this->stats['banned'] }} })" x-text="display">
                    </flux:heading>
                    <flux:subheading class="text-xs! mt-1">Restricted accounts</flux:subheading>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-500/15 flex items-center justify-center shrink-0">
                    <flux:icon.no-symbol class="size-5 text-red-500" />
                </div>
            </div>
        </flux:card>
    </div>

    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800">

        {{-- Filters --}}
        <div class="flex items-center flex-wrap gap-3 px-5 py-3 border-b dark:border-zinc-600">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass"
                placeholder="Search customers..." class="max-w-xs" />

            <div class="ms-auto flex items-center gap-3">
                <flux:select wire:model.live="status" class="w-36">
                    <flux:select.option value="">All Status</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="inactive">Inactive</flux:select.option>
                    <flux:select.option value="banned">Banned</flux:select.option>
                </flux:select>
            </div>
        </div>

        {{-- Table --}}
        <flux:table :paginate="$this->customers">
            <flux:table.columns>
                <flux:table.column class="ps-5!">ID</flux:table.column>
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column>Phone</flux:table.column>
                <flux:table.column>Orders</flux:table.column>
                <flux:table.column>Amount Spent</flux:table.column>
                <flux:table.column>Joined</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->customers as $customer)
                    <flux:table.row :key="$customer->id">

                        <flux:table.cell class="ps-5!">
                            <flux:subheading>#{{ $customer->id }}</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <div class="shrink-0">
                                    @if ($customer->avatar)
                                        <flux:avatar :src="$customer->avatar" :alt="$customer->name" circle
                                            size="sm" />
                                    @else
                                        <flux:avatar :name="$customer->name" circle size="sm" />
                                    @endif
                                </div>
                                <div>
                                    <flux:heading size="sm">{{ $customer->name }}</flux:heading>
                                    <flux:subheading>{{ $customer->email }}</flux:subheading>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ $customer->phone_number ?? '—' }}</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ $customer->orders_count }}</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>{{ number_format($customer->amount_spent ?? 0, 2) }}</flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:subheading>
                                {{ $customer->created_at->format('M d, Y') }}
                            </flux:subheading>
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $statusColor = match ($customer->status) {
                                    'active' => 'green',
                                    'banned' => 'red',
                                    default => 'yellow',
                                };
                            @endphp
                            <flux:badge size="sm" :color="$statusColor" variant="soft" class="capitalize">
                                {{ $customer->status }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="pe-4!">
                            <div class="flex items-center justify-end gap-1">
                                {{-- <flux:button icon="eye" variant="ghost" size="sm" tooltip="View"
                                        :href="route('admin.customers.show', $customer->id)" wire:navigate /> --}}

                                <flux:button icon="pencil-square" variant="ghost" size="sm" tooltip="Edit"
                                    icon-variant="outline" :href="route('admin.customers.edit', $customer->id)"
                                    wire:navigate class="cursor-pointer" />

                                <flux:button icon="trash" variant="ghost" size="sm"
                                    class="text-red-500! cursor-pointer" tooltip="Delete" icon-variant="outline"
                                    wire:confirm="Are you sure? This will permanently delete {{ $customer->email }} and all related data."
                                    wire:click="delete({{ $customer->id }})" />

                            </div>
                        </flux:table.cell>

                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-16">
                            <div class="flex flex-col items-center gap-3">
                                <flux:icon.users class="size-10 text-zinc-300" />
                                <div>
                                    <flux:heading size="sm">No Customers Found</flux:heading>
                                    <flux:subheading class="mt-0.5">
                                        {{ $this->search || $this->status ? 'No customers match your filters.' : 'No customers have been added yet.' }}
                                    </flux:subheading>
                                </div>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

</div>
