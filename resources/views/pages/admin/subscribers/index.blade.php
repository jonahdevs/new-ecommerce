<?php

use App\Exports\SubscribersExport;
use App\Models\Subscriber;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

new #[Layout('layouts::app')] #[Title('Subscribers | Admin')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterInterest = '';

    #[Url]
    public int $perPage = 25;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterInterest(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    /** @return array{total: int, confirmed: int, pending: int, unsubscribed: int} */
    #[Computed]
    public function stats(): array
    {
        return [
            'total'        => Subscriber::count(),
            'confirmed'    => Subscriber::confirmed()->count(),
            'pending'      => Subscriber::pending()->count(),
            'unsubscribed' => Subscriber::unsubscribed()->count(),
        ];
    }

    #[Computed]
    public function subscribers()
    {
        return Subscriber::query()
            ->when($this->search, fn ($q) => $q->where('email', 'like', '%'.$this->search.'%'))
            ->when($this->filterStatus === 'confirmed', fn ($q) => $q->confirmed())
            ->when($this->filterStatus === 'pending', fn ($q) => $q->pending())
            ->when($this->filterStatus === 'unsubscribed', fn ($q) => $q->unsubscribed())
            ->when($this->filterInterest, fn ($q) => $q->whereJsonContains('interests', $this->filterInterest))
            ->latest()
            ->paginate($this->perPage);
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Subscribers</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush
            <flux:heading size="xl">Subscribers</flux:heading>
            <flux:subheading>Newsletter subscribers from the storefront.</flux:subheading>
        </div>

        <flux:dropdown>
            <flux:button icon="arrow-down-tray" icon-trailing="chevron-down">Export</flux:button>
            <flux:menu>
                <flux:menu.item icon="table-cells"
                    href="{{ route('admin.subscribers.export', array_filter(['status' => $filterStatus, 'interest' => $filterInterest, 'q' => $search])) }}">
                    Excel (.xlsx)
                </flux:menu.item>
                <flux:menu.item icon="document-text"
                    href="{{ route('admin.subscribers.export', array_filter(['format' => 'csv', 'status' => $filterStatus, 'interest' => $filterInterest, 'q' => $search])) }}">
                    CSV (.csv)
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>

    {{-- KPIs --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <flux:card class="flex items-center gap-4">
            <flux:icon.users class="size-9 text-zinc-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ number_format($this->stats['total']) }}</div>
                <flux:text size="sm">Total</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.check-circle class="size-9 text-emerald-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ number_format($this->stats['confirmed']) }}</div>
                <flux:text size="sm">Confirmed</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.clock class="size-9 text-amber-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ number_format($this->stats['pending']) }}</div>
                <flux:text size="sm">Pending</flux:text>
            </div>
        </flux:card>
        <flux:card class="flex items-center gap-4">
            <flux:icon.x-circle class="size-9 text-red-400" />
            <div>
                <div class="text-2xl font-semibold tabular-nums dark:text-white">{{ number_format($this->stats['unsubscribed']) }}</div>
                <flux:text size="sm">Unsubscribed</flux:text>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">
        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search email…"
                icon="magnifying-glass" clearable class="max-w-xs" />

            <flux:spacer />

            <flux:select wire:model.live="filterStatus" class="w-40">
                <flux:select.option value="">All statuses</flux:select.option>
                <flux:select.option value="confirmed">Confirmed</flux:select.option>
                <flux:select.option value="pending">Pending</flux:select.option>
                <flux:select.option value="unsubscribed">Unsubscribed</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="filterInterest" class="w-44">
                <flux:select.option value="">All interests</flux:select.option>
                <flux:select.option value="new-products">New products</flux:select.option>
                <flux:select.option value="seasonal-catalogs">Catalogs</flux:select.option>
                <flux:select.option value="projects">Projects</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="perPage" class="w-28">
                <flux:select.option value="25">25 / page</flux:select.option>
                <flux:select.option value="50">50 / page</flux:select.option>
                <flux:select.option value="100">100 / page</flux:select.option>
            </flux:select>
        </div>

        <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Interests</flux:table.column>
                <flux:table.column>Source</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Subscribed</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->subscribers as $subscriber)
                    <flux:table.row :key="$subscriber->id">
                        <flux:table.cell variant="strong">{{ $subscriber->email }}</flux:table.cell>

                        <flux:table.cell>
                            @php
                                $labels = [
                                    'new-products'      => 'New products',
                                    'seasonal-catalogs' => 'Catalogs',
                                    'projects'          => 'Projects',
                                ];
                            @endphp
                            <div class="flex flex-wrap gap-1">
                                @foreach (($subscriber->interests ?? []) as $interest)
                                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $labels[$interest] ?? $interest }}</flux:badge>
                                @endforeach
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text size="sm" class="font-mono text-zinc-400">{{ $subscriber->source ?? '—' }}</flux:text>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($subscriber->isUnsubscribed())
                                <flux:badge size="sm" color="red" inset="top bottom">Unsubscribed</flux:badge>
                            @elseif ($subscriber->isConfirmed())
                                <flux:badge size="sm" color="green" inset="top bottom">Confirmed</flux:badge>
                            @else
                                <flux:badge size="sm" color="yellow" inset="top bottom">Pending</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:text size="sm" class="text-zinc-400">
                                {{ $subscriber->subscribed_at?->format('d M Y') ?? '—' }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-16 text-center text-zinc-400">
                            No subscribers match your filters.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->subscribers->hasPages())
            <div class="border-t border-zinc-200 px-6 pb-3 dark:border-zinc-700">
                <flux:pagination :paginator="$this->subscribers" />
            </div>
        @endif
    </flux:card>
</div>
