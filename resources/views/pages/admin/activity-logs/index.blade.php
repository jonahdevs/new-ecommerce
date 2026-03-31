<?php

namespace App\Livewire\Admin\ActivityLogs;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Computed, Layout, Title, Url};
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Builder;

new #[Title('Activity Logs')] #[Layout('layouts.admin')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $eventType = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $causerId = '';

    public int $perPage = 50;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEventType(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'eventType', 'dateFrom', 'dateTo', 'causerId']);
        $this->resetPage();
    }

    #[Computed]
    public function activities()
    {
        return Activity::query()
            ->with(['subject', 'causer'])
            ->when($this->search, function (Builder $query) {
                $query->where(function ($q) {
                    $q->where('description', 'like', "%{$this->search}%")
                        ->orWhere('properties', 'like', "%{$this->search}%")
                        ->orWhereHas('causer', function ($q) {
                            $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->eventType, function (Builder $query) {
                match ($this->eventType) {
                    'orders' => $query->where('description', 'like', 'order_%'),
                    'payments' => $query->where('description', 'like', 'payment_%'),
                    'inventory' => $query->where('description', 'like', 'inventory_%'),
                    'sap' => $query->where('description', 'like', 'sap_%'),
                    'quotes' => $query->where('description', 'like', 'quote_%'),
                    'users' => $query->where('description', 'like', 'user_%'),
                    'webhooks' => $query->where('description', 'like', 'webhook_%'),
                    'errors' => $query->where('description', 'like', '%_failed')->orWhere('description', 'like', '%_error'),
                    default => null,
                };
            })
            ->when($this->dateFrom, fn(Builder $q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn(Builder $q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->causerId, fn(Builder $q) => $q->where('causer_id', $this->causerId))
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function eventTypes(): array
    {
        return [
            '' => 'All Events',
            'orders' => 'Orders',
            'payments' => 'Payments',
            'inventory' => 'Inventory',
            'sap' => 'SAP Integration',
            'quotes' => 'Quotations',
            'users' => 'Users',
            'webhooks' => 'Webhooks',
            'errors' => 'Errors & Failures',
        ];
    }

    public function getEventIcon(string $description): string
    {
        return match (true) {
            str_contains($description, 'payment') => '💰',
            str_contains($description, 'order') => '📦',
            str_contains($description, 'inventory') => '📊',
            str_contains($description, 'sap') => '🔄',
            str_contains($description, 'quote') => '📝',
            str_contains($description, 'user') => '👤',
            str_contains($description, 'webhook') => '🔔',
            default => '•',
        };
    }

    public function getEventColor(string $description): string
    {
        return match (true) {
            str_contains($description, 'failed') || str_contains($description, 'cancelled') => 'text-red-600 dark:text-red-400',
            str_contains($description, 'confirmed') || str_contains($description, 'paid') || str_contains($description, 'success') || str_contains($description, 'accepted') => 'text-green-600 dark:text-green-400',
            str_contains($description, 'initiated') || str_contains($description, 'requested') => 'text-yellow-600 dark:text-yellow-400',
            default => 'text-blue-600 dark:text-blue-400',
        };
    }

    public function getEventLabel(string $description): string
    {
        return str_replace('_', ' ', ucwords($description, '_'));
    }
};
?>

<div>
    {{-- Breadcrumb --}}
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Reports & Analytics</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Activity Logs</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="mb-1">Activity Logs</flux:heading>
            <flux:subheading>System activity and audit trail</flux:subheading>
        </div>
    </div>

    {{-- Main card --}}
    <flux:card class="p-0">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">

            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search activities..."
                class="max-w-xs" clearable />

            <div class="flex items-center gap-2 ms-auto flex-wrap">

                <flux:select wire:model.live="eventType" class="w-52">
                    <flux:select.option value="">All Events</flux:select.option>
                    @foreach ($this->eventTypes as $value => $label)
                        @if ($value !== '')
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endif
                    @endforeach
                </flux:select>

                {{-- Per page --}}
                <flux:select wire:model.live="perPage" class="w-24">
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>

                {{-- Date filters dropdown --}}
                <flux:dropdown>
                    <flux:button icon="calendar" variant="ghost" size="sm">
                        Date Range
                        @if ($dateFrom || $dateTo)
                            <span class="w-2 h-2 rounded-full bg-indigo-500 ms-1"></span>
                        @endif
                    </flux:button>

                    <flux:menu class="min-w-80 z-10!">
                        <div class="p-4 space-y-4">
                            <flux:heading size="sm">Filter by Date</flux:heading>

                            <div class="space-y-2">
                                <flux:label>Date Range</flux:label>
                                <flux:input type="date" wire:model.live="dateFrom" placeholder="From date" />
                                <flux:input type="date" wire:model.live="dateTo" placeholder="To date" />
                            </div>
                        </div>

                        <flux:menu.separator />

                        <div class="p-2">
                            <flux:button variant="ghost" size="sm"
                                wire:click="$set('dateFrom', ''); $set('dateTo', '')" class="w-full">
                                Clear Dates
                            </flux:button>
                        </div>
                    </flux:menu>
                </flux:dropdown>

                {{-- Clear filters --}}
                @if ($search || $eventType || $dateFrom || $dateTo)
                    <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark">
                        Clear
                    </flux:button>
                @endif

            </div>
        </div>

        {{-- Active filter tags --}}
        @if ($eventType || $dateFrom || $dateTo)
            <div class="flex flex-wrap gap-2 px-5 py-2 border-b border-zinc-200 dark:border-zinc-700">
                <span
                    class="text-xs font-semibold text-zinc-400 uppercase tracking-wider self-center me-1">Active:</span>

                @if ($eventType)
                    <flux:badge size="sm" variant="flat" closable wire:click="$set('eventType', '')">
                        Type: {{ $this->eventTypes[$eventType] ?? $eventType }}
                    </flux:badge>
                @endif

                @if ($dateFrom)
                    <flux:badge size="sm" variant="flat" closable wire:click="$set('dateFrom', '')">
                        From: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }}
                    </flux:badge>
                @endif

                @if ($dateTo)
                    <flux:badge size="sm" variant="flat" closable wire:click="$set('dateTo', '')">
                        To: {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
                    </flux:badge>
                @endif
            </div>
        @endif

        {{-- Activity List --}}
        <div class="space-y-1">
            @forelse($this->activities as $activity)
                <div
                    class="flex items-start gap-4 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                    <div class="text-3xl shrink-0">
                        {{ $this->getEventIcon($activity->description) }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <p class="text-base font-semibold {{ $this->getEventColor($activity->description) }}">
                                    {{ $this->getEventLabel($activity->description) }}
                                </p>

                                <div class="flex items-center gap-3 mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    <span>by {{ $activity->causer?->name ?? 'System' }}</span>
                                    <span>•</span>
                                    <time>{{ $activity->created_at->format('M j, Y g:i A') }}</time>
                                    <span>•</span>
                                    <span class="text-xs">{{ $activity->created_at->diffForHumans() }}</span>
                                </div>

                                @if ($activity->subject)
                                    <div class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                                        @if ($activity->subject_type === 'App\Models\Order')
                                            <span class="font-medium">Order:</span>
                                            <a href="{{ route('admin.orders.show', $activity->subject) }}"
                                                wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">
                                                #{{ $activity->subject->reference ?? 'N/A' }}
                                            </a>
                                            @if ($activity->properties->has('total'))
                                                • {{ format_currency($activity->properties->get('total')) }}
                                            @endif
                                        @elseif($activity->subject_type === 'App\Models\Payment')
                                            <span class="font-medium">Payment:</span>
                                            @if ($activity->properties->has('order_reference'))
                                                Order #{{ $activity->properties->get('order_reference') }}
                                            @endif
                                            @if ($activity->properties->has('amount'))
                                                • {{ format_currency($activity->properties->get('amount')) }}
                                            @endif
                                            @if ($activity->properties->has('transaction_id'))
                                                • TX: {{ $activity->properties->get('transaction_id') }}
                                            @endif
                                        @elseif($activity->subject_type === 'App\Models\Quote')
                                            <span class="font-medium">Quote:</span>
                                            <a href="{{ route('admin.quotations.show', $activity->subject) }}"
                                                wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">
                                                #{{ $activity->subject->reference ?? 'N/A' }}
                                            </a>
                                        @elseif($activity->subject_type === 'App\Models\User')
                                            <span class="font-medium">User:</span>
                                            {{ $activity->subject->email ?? 'User' }}
                                        @else
                                            <span
                                                class="font-medium">{{ class_basename($activity->subject_type) }}:</span>
                                            #{{ $activity->subject_id }}
                                        @endif
                                    </div>
                                @endif

                                @if ($activity->properties->has('reason'))
                                    <div class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        <span class="font-medium">Reason:</span>
                                        {{ $activity->properties->get('reason') }}
                                    </div>
                                @endif

                                @if ($activity->properties->has('error'))
                                    <div class="mt-2 text-sm text-red-600 dark:text-red-400">
                                        <span class="font-medium">Error:</span>
                                        {{ $activity->properties->get('error') }}
                                    </div>
                                @endif

                                @if ($activity->properties->has('ip_address'))
                                    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-500">
                                        IP: {{ $activity->properties->get('ip_address') }}
                                    </div>
                                @endif
                            </div>

                            <div class="shrink-0">
                                <flux:modal.trigger name="activity-details-{{ $activity->id }}">
                                    <flux:button size="sm" variant="ghost">Details</flux:button>
                                </flux:modal.trigger>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Activity Details Modal --}}
                <flux:modal name="activity-details-{{ $activity->id }}" class="max-w-2xl">
                    <div>
                        <flux:heading size="lg" class="mb-4">Activity Details</flux:heading>

                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Event</p>
                                <p class="text-base {{ $this->getEventColor($activity->description) }}">
                                    {{ $this->getEventLabel($activity->description) }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Performed By</p>
                                <p class="text-sm">{{ $activity->causer?->name ?? 'System' }}</p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Timestamp</p>
                                <p class="text-sm">{{ $activity->created_at->format('F j, Y g:i:s A') }}</p>
                            </div>

                            @if ($activity->subject)
                                <div>
                                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Subject</p>
                                    <p class="text-sm">{{ class_basename($activity->subject_type) }}
                                        #{{ $activity->subject_id }}</p>
                                </div>
                            @endif

                            @if ($activity->properties->isNotEmpty())
                                <div>
                                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Properties</p>
                                    <pre class="text-xs bg-zinc-100 dark:bg-zinc-800 p-3 rounded overflow-x-auto">{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                        </div>

                        <div class="mt-6 flex justify-end">
                            <flux:button x-on:click="$dispatch('close-modal')">Close</flux:button>
                        </div>
                    </div>
                </flux:modal>
            @empty
                <div class="text-center py-12 text-zinc-500 dark:text-zinc-400">
                    <p>No activity logs found</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-3 border-t border-zinc-200 dark:border-zinc-700">
            {{ $this->activities->links() }}
        </div>
    </flux:card>
</div>
