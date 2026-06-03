<?php

use App\Support\ActivityLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new #[Layout('layouts::app')] class extends Component {
    use WithPagination;

    public string $logName = '';

    #[Url]
    public string $filterEvent = '';

    #[Url]
    public string $filterDate = '';

    #[Url]
    public int $perPage = 25;

    public function mount(string $logName): void
    {
        abort_unless(ActivityLog::exists($logName), 404);
        $this->logName = $logName;
    }

    public function updatedFilterEvent(): void { $this->resetPage(); }
    public function updatedFilterDate(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    public function config(): array
    {
        return ActivityLog::metaFor($this->logName);
    }

    #[Computed]
    public function activities()
    {
        return Activity::with(['causer', 'subject'])
            ->where('log_name', $this->logName)
            ->when($this->filterEvent, fn ($q) => $q->where('event', $this->filterEvent))
            ->when($this->filterDate === 'today', fn ($q) => $q->whereDate('created_at', today()))
            ->when($this->filterDate === 'week', fn ($q) => $q->where('created_at', '>=', now()->startOfWeek()))
            ->when($this->filterDate === 'month', fn ($q) => $q->where('created_at', '>=', now()->startOfMonth()))
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function allLogs(): array
    {
        return ActivityLog::logs();
    }

    public function subjectLabel(Activity $activity): string
    {
        return ActivityLog::subjectLabel($this->logName, $activity->subject, $activity->subject_id);
    }

    public function subjectRoute(Activity $activity): ?string
    {
        return ActivityLog::subjectRoute($this->logName, $activity->subject);
    }

    public function rendering($view): void
    {
        $view->title($this->config()['label'].' Activity — Admin');
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Activity Log</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $this->config()['label'] }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush
            <flux:heading size="xl">{{ $this->config()['label'] }} Activity</flux:heading>
            <flux:subheading>Full audit trail of changes to {{ strtolower($this->config()['label']) }}.</flux:subheading>
        </div>

        {{-- Jump to another log --}}
        <flux:select wire:model.live="logName" class="w-52"
            x-on:change="window.location.href = '{{ url('/admin/activity') }}/' + $el.value">
            @foreach ($this->allLogs as $key => $meta)
                <flux:select.option value="{{ $key }}" :selected="$key === $logName">
                    {{ $meta['label'] }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">
        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <flux:select wire:model.live="filterEvent" class="w-40">
                    <flux:select.option value="">All events</flux:select.option>
                    <flux:select.option value="created">Created</flux:select.option>
                    <flux:select.option value="updated">Updated</flux:select.option>
                    <flux:select.option value="deleted">Deleted</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="filterDate" class="w-36">
                    <flux:select.option value="">All time</flux:select.option>
                    <flux:select.option value="today">Today</flux:select.option>
                    <flux:select.option value="week">This week</flux:select.option>
                    <flux:select.option value="month">This month</flux:select.option>
                </flux:select>
            </div>

            <flux:select wire:model.live="perPage" class="w-28">
                <flux:select.option value="25">25 / page</flux:select.option>
                <flux:select.option value="50">50 / page</flux:select.option>
                <flux:select.option value="100">100 / page</flux:select.option>
            </flux:select>
        </div>

        <flux:table container:class="[&_th:first-child]:pl-6 [&_th:last-child]:pr-6 [&_td:first-child]:pl-6 [&_td:last-child]:pr-6">
            <flux:table.columns class="bg-zinc-50 dark:bg-zinc-800/60">
                <flux:table.column>When</flux:table.column>
                <flux:table.column>Subject</flux:table.column>
                <flux:table.column>Event</flux:table.column>
                <flux:table.column>Changes</flux:table.column>
                <flux:table.column align="end">By</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->activities as $activity)
                    @php
                        $subjectLabel = $this->subjectLabel($activity);
                        $subjectRoute = $this->subjectRoute($activity);
                    @endphp
                    <flux:table.row wire:key="activity-{{ $activity->id }}">

                        {{-- When --}}
                        <flux:table.cell class="whitespace-nowrap">
                            <flux:tooltip :content="$activity->created_at->format('d M Y, H:i:s')">
                                <span class="text-[13px] text-ink-3 cursor-default">
                                    {{ $activity->created_at->diffForHumans() }}
                                </span>
                            </flux:tooltip>
                        </flux:table.cell>

                        {{-- Subject --}}
                        <flux:table.cell>
                            @if ($subjectRoute)
                                <a href="{{ $subjectRoute }}" wire:navigate
                                    class="text-[13px] font-medium text-brand-600 hover:underline">
                                    {{ $subjectLabel }}
                                </a>
                            @else
                                <span class="text-[13px] font-medium text-ink">{{ $subjectLabel }}</span>
                            @endif
                        </flux:table.cell>

                        {{-- Event badge --}}
                        <flux:table.cell>
                            @php
                                $eventColor = match ($activity->event) {
                                    'created' => 'green',
                                    'updated' => 'blue',
                                    'deleted' => 'red',
                                    default   => 'zinc',
                                };
                            @endphp
                            <flux:badge :color="$eventColor" size="sm">{{ ucfirst($activity->event ?? 'unknown') }}</flux:badge>
                        </flux:table.cell>

                        {{-- Changes --}}
                        <flux:table.cell>
                            <x-admin.activity-changes :activity="$activity" :log-name="$logName" />
                        </flux:table.cell>

                        {{-- By --}}
                        <flux:table.cell align="end" class="whitespace-nowrap">
                            <span class="text-[13px] text-ink-2">
                                {{ $activity->causer?->name ?? $activity->getProperty('source') ?? 'System' }}
                            </span>
                        </flux:table.cell>

                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-16 text-center">
                            <flux:icon.clock variant="outline" class="mx-auto size-8 text-ink-4" />
                            <div class="mt-3 text-[14px] text-ink-3">No activity recorded yet.</div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        @if ($this->activities->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $this->activities->links() }}
            </div>
        @endif
    </flux:card>
</div>
