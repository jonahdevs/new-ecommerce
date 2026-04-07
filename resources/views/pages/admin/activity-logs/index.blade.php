<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Computed, Title, Url};
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Builder;

new #[Title('Activity Logs')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $eventType = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public int $perPage = 10;

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

    public function setDateRange(string $from, string $to): void
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'eventType', 'dateFrom', 'dateTo']);
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
                            $q->where('name', 'like', "%{$this->search}%")
                                ->orWhere('email', 'like', "%{$this->search}%");
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
                    'errors' => $query->where('description', 'like', '%_failed')
                        ->orWhere('description', 'like', '%_error'),
                    default => null,
                };
            })
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $q) => $q->whereDate('created_at', '<=', $this->dateTo))
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

    public function getEventIcon(string $description): array
    {
        return match (true) {
            str_contains($description, 'payment') => ['icon' => 'banknotes', 'color' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-50 dark:bg-emerald-950/50'],
            str_contains($description, 'order') => ['icon' => 'shopping-bag', 'color' => 'text-blue-600 dark:text-blue-400', 'bg' => 'bg-blue-50 dark:bg-blue-950/50'],
            str_contains($description, 'inventory') => ['icon' => 'chart-bar', 'color' => 'text-violet-600 dark:text-violet-400', 'bg' => 'bg-violet-50 dark:bg-violet-950/50'],
            str_contains($description, 'sap') => ['icon' => 'arrow-path', 'color' => 'text-indigo-600 dark:text-indigo-400', 'bg' => 'bg-indigo-50 dark:bg-indigo-950/50'],
            str_contains($description, 'quote') => ['icon' => 'document-text', 'color' => 'text-amber-600 dark:text-amber-400', 'bg' => 'bg-amber-50 dark:bg-amber-950/50'],
            str_contains($description, 'user') => ['icon' => 'user', 'color' => 'text-teal-600 dark:text-teal-400', 'bg' => 'bg-teal-50 dark:bg-teal-950/50'],
            str_contains($description, 'webhook') => ['icon' => 'bell', 'color' => 'text-pink-600 dark:text-pink-400', 'bg' => 'bg-pink-50 dark:bg-pink-950/50'],
            default => ['icon' => 'information-circle', 'color' => 'text-zinc-500 dark:text-zinc-400', 'bg' => 'bg-zinc-100 dark:bg-zinc-800'],
        };
    }

    public function getEventLabel(string $description): string
    {
        return str_replace('_', ' ', ucwords($description, '_'));
    }

    public function getEventLabelColor(string $description): string
    {
        return match (true) {
            str_contains($description, 'failed') || str_contains($description, 'cancelled') => 'text-red-600 dark:text-red-400',
            str_contains($description, 'confirmed') || str_contains($description, 'paid') || str_contains($description, 'completed') || str_contains($description, 'accepted') => 'text-green-600 dark:text-green-400',
            str_contains($description, 'initiated') || str_contains($description, 'requested') || str_contains($description, 'pending') => 'text-amber-600 dark:text-amber-400',
            default => 'text-zinc-800 dark:text-zinc-100',
        };
    }

    public function getSubjectLink(Activity $activity): ?string
    {
        if (! $activity->subject) {
            return null;
        }

        return match ($activity->subject_type) {
            'App\Models\Order' => route('admin.orders.show', $activity->subject),
            'App\Models\Quote' => route('admin.quotations.show', $activity->subject),
            default => null,
        };
    }

    public function getSubjectLabel(Activity $activity): string
    {
        if (! $activity->subject) {
            return '—';
        }

        $type = class_basename($activity->subject_type);

        return match ($activity->subject_type) {
            'App\Models\Order' => 'Order #'.($activity->subject->reference ?? $activity->subject_id),
            'App\Models\Quote' => 'Quote #'.($activity->subject->reference ?? $activity->subject_id),
            'App\Models\Payment' => 'Payment #'.$activity->subject_id,
            'App\Models\User' => $activity->subject->email ?? 'User #'.$activity->subject_id,
            default => "{$type} #{$activity->subject_id}",
        };
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-2">
        <flux:breadcrumbs.item :href="route('admin.dashboard')" icon="home" icon-variant="outline" wire:navigate />
        <flux:breadcrumbs.item>Activity Logs</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Activity Logs</flux:heading>
            <flux:subheading>System activity and audit trail from Spatie Activity Log</flux:subheading>
        </div>
    </div>

    <flux:card class="p-0 **:data-flux-columns:bg-zinc-50 dark:**:data-flux-columns:bg-zinc-800 mt-6">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search events, properties, causer..." class="max-w-xs" clearable />

            <div class="ms-auto flex items-center gap-2 flex-wrap">
                <flux:select wire:model.live="eventType" class="w-48">
                    @foreach ($this->eventTypes as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="perPage" class="w-24">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>

                <div class="relative" wire:ignore>
                    <input type="text" readonly
                        class="activity-logs-date-range w-60 pl-8 pr-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 cursor-pointer focus:outline-none focus:ring-2 focus:ring-zinc-300 hover:border-zinc-400 transition-colors"
                        placeholder="All dates" />
                    <flux:icon.calendar-days class="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400 pointer-events-none" />
                </div>

                <flux:icon.loading wire:loading wire:target="setDateRange" class="size-3.5 text-zinc-400" />

                @if ($search || $eventType || $dateFrom || $dateTo)
                    <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="x-mark">
                        Clear
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Active filter chips --}}
        @if ($eventType || $dateFrom || $dateTo)
            <div class="flex flex-wrap gap-2 px-5 py-2 border-b border-zinc-200 dark:border-zinc-700">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider self-center me-1">Active:</span>

                @if ($eventType)
                    <flux:badge size="sm" variant="flat" closable wire:click="$set('eventType', '')">
                        {{ $this->eventTypes[$eventType] ?? $eventType }}
                    </flux:badge>
                @endif

                @if ($dateFrom || $dateTo)
                    <flux:badge size="sm" variant="flat" closable wire:click="setDateRange('', '')">
                        {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('M d') : '…' }}
                        –
                        {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('M d, Y') : '…' }}
                    </flux:badge>
                @endif
            </div>
        @endif

        {{-- Table --}}
        <flux:table :paginate="$this->activities">
            <flux:table.columns>
                <flux:table.column class="ps-5! w-72">Event</flux:table.column>
                <flux:table.column>Subject</flux:table.column>
                <flux:table.column>Performed by</flux:table.column>
                <flux:table.column>When</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->activities as $activity)
                    @php
                        $iconData = $this->getEventIcon($activity->description);
                        $subjectLink = $this->getSubjectLink($activity);
                        $subjectLabel = $this->getSubjectLabel($activity);
                    @endphp

                    <flux:table.row :key="$activity->id">

                        {{-- Event --}}
                        <flux:table.cell class="ps-5!">
                            <div class="flex items-center gap-3">
                                <div class="shrink-0 w-8 h-8 rounded-lg {{ $iconData['bg'] }} flex items-center justify-center">
                                    <flux:icon :name="$iconData['icon']" class="size-4 {{ $iconData['color'] }}" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium {{ $this->getEventLabelColor($activity->description) }}">
                                        {{ $this->getEventLabel($activity->description) }}
                                    </p>
                                    @if ($activity->properties->has('error'))
                                        <p class="text-xs text-red-500 dark:text-red-400 truncate max-w-52">
                                            {{ $activity->properties->get('error') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>

                        {{-- Subject --}}
                        <flux:table.cell>
                            @if ($subjectLink)
                                <a href="{{ $subjectLink }}" wire:navigate
                                    class="text-sm text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                    {{ $subjectLabel }}
                                </a>
                            @else
                                <flux:subheading>{{ $subjectLabel }}</flux:subheading>
                            @endif
                            @if ($activity->properties->has('total'))
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    {{ format_currency($activity->properties->get('total')) }}
                                </p>
                            @endif
                        </flux:table.cell>

                        {{-- Causer --}}
                        <flux:table.cell>
                            @if ($activity->causer)
                                <div class="flex items-center gap-2">
                                    <flux:avatar size="xs" circle :name="$activity->causer->name" />
                                    <div>
                                        <p class="text-sm text-zinc-800 dark:text-zinc-100">{{ $activity->causer->name }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $activity->causer->email }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-zinc-400 dark:text-zinc-500">
                                    <flux:icon name="cog-6-tooth" class="size-4" />
                                    <flux:subheading>System</flux:subheading>
                                </div>
                            @endif
                        </flux:table.cell>

                        {{-- When --}}
                        <flux:table.cell>
                            <flux:subheading>{{ $activity->created_at->format('M j, Y') }}</flux:subheading>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                                {{ $activity->created_at->format('g:i A') }} · {{ $activity->created_at->diffForHumans() }}
                            </p>
                        </flux:table.cell>

                        {{-- Details --}}
                        <flux:table.cell class="pe-4!">
                            <flux:modal.trigger name="activity-{{ $activity->id }}">
                                <flux:button size="sm" variant="ghost" class="cursor-pointer">Details</flux:button>
                            </flux:modal.trigger>
                        </flux:table.cell>

                    </flux:table.row>

                    {{-- Details Modal --}}
                    <flux:modal name="activity-{{ $activity->id }}" class="max-w-xl">
                        <flux:heading size="lg" class="mb-1">{{ $this->getEventLabel($activity->description) }}</flux:heading>
                        <flux:subheading class="mb-5">{{ $activity->created_at->format('F j, Y g:i:s A') }}</flux:subheading>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Performed by</p>
                                    <p class="text-zinc-800 dark:text-zinc-100">{{ $activity->causer?->name ?? 'System' }}</p>
                                    @if ($activity->causer)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $activity->causer->email }}</p>
                                    @endif
                                </div>

                                @if ($activity->subject)
                                    <div>
                                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Subject</p>
                                        <p class="text-zinc-800 dark:text-zinc-100">{{ $subjectLabel }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ class_basename($activity->subject_type) }}</p>
                                    </div>
                                @endif
                            </div>

                            @if ($activity->properties->isNotEmpty())
                                <div>
                                    <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2">Properties</p>
                                    <pre class="text-xs bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 p-3 rounded-lg overflow-x-auto max-h-72">{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                        </div>

                        <div class="mt-5 flex justify-end">
                            <flux:button x-on:click="$dispatch('close-modal')">Close</flux:button>
                        </div>
                    </flux:modal>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <flux:icon.clipboard-document-list class="size-10 text-zinc-300 dark:text-zinc-600" />
                                <div>
                                    <flux:heading size="sm">No activity logs found</flux:heading>
                                    <flux:subheading class="mt-0.5">
                                        @if ($search || $eventType || $dateFrom || $dateTo)
                                            No results match your current filters.
                                        @else
                                            Activity will appear here as actions are performed.
                                        @endif
                                    </flux:subheading>
                                </div>
                                @if ($search || $eventType || $dateFrom || $dateTo)
                                    <flux:button variant="ghost" size="sm" wire:click="resetFilters">
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
</div>

@assets
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
@endassets

@script
<script>
    function waitForLibraries(cb) {
        if (typeof jQuery !== 'undefined' && typeof moment !== 'undefined' && typeof jQuery.fn.daterangepicker !== 'undefined') {
            cb();
        } else {
            setTimeout(() => waitForLibraries(cb), 100);
        }
    }

    function initDateRangePicker() {
        const el = $('.activity-logs-date-range').first();
        if (!el.length) return;

        if (el.data('daterangepicker')) {
            el.data('daterangepicker').remove();
        }

        el.daterangepicker({
            autoUpdateInput: false,
            opens: 'left',
            showDropdowns: true,
            alwaysShowCalendars: false,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            },
            locale: {
                format: 'MMM DD, YYYY',
                separator: ' – ',
                cancelLabel: 'Clear',
            },
        }, function(start, end) {
            $wire.setDateRange(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            el.val(start.format('MMM DD, YYYY') + ' – ' + end.format('MMM DD, YYYY'));
        });

        el.on('cancel.daterangepicker', function() {
            $wire.setDateRange('', '');
            el.val('');
        });

        if ($wire.dateFrom && $wire.dateTo) {
            el.val(moment($wire.dateFrom).format('MMM DD, YYYY') + ' – ' + moment($wire.dateTo).format('MMM DD, YYYY'));
        }
    }

    waitForLibraries(() => initDateRangePicker());
</script>
@endscript
