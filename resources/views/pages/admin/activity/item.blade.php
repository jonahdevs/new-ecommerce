<?php

use App\Support\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new #[Layout('layouts::app')] class extends Component {
    use WithPagination;

    public string $logName = '';

    public int|string $subjectId = 0;

    #[Url]
    public string $filterEvent = '';

    #[Url]
    public int $perPage = 25;

    public function mount(string $logName, int|string $id): void
    {
        abort_unless(ActivityLog::exists($logName), 404);

        $this->logName = $logName;
        $this->subjectId = $id;
    }

    public function updatedFilterEvent(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    public function config(): array
    {
        return ActivityLog::metaFor($this->logName);
    }

    #[Computed]
    public function subject(): ?Model
    {
        $modelClass = $this->config()['model'];
        $query = $modelClass::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        return $query->find($this->subjectId);
    }

    #[Computed]
    public function activities()
    {
        $modelClass = $this->config()['model'];

        return Activity::with('causer')
            ->where('log_name', $this->logName)
            ->where('subject_type', (new $modelClass)->getMorphClass())
            ->where('subject_id', $this->subjectId)
            ->when($this->filterEvent, fn ($q) => $q->where('event', $this->filterEvent))
            ->latest()
            ->paginate($this->perPage);
    }

    public function subjectLabel(): string
    {
        return ActivityLog::subjectLabel($this->logName, $this->subject, $this->subjectId);
    }

    public function subjectRoute(): ?string
    {
        return ActivityLog::subjectRoute($this->logName, $this->subject);
    }

    public function formatValue(string $field, mixed $value): string
    {
        return ActivityLog::formatValue($this->logName, $field, $value);
    }

    public function rendering($view): void
    {
        $view->title($this->subjectLabel().' — Activity');
    }
}; ?>

<div>
    <div class="flex items-start justify-between gap-4">
        <div>
            @push('breadcrumbs')
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('admin.activity.show', $logName)" wire:navigate>{{ $this->config()['label'] }} Activity</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $this->subjectLabel() }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endpush
            <flux:heading size="xl">{{ $this->subjectLabel() }}</flux:heading>
            <flux:subheading>Change history for this {{ str($this->config()['label'])->lower()->singular() }}.</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @if ($this->subjectRoute())
                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="$this->subjectRoute()" wire:navigate>
                    Edit
                </flux:button>
            @endif
            <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('admin.activity.show', $logName)" wire:navigate>
                All {{ strtolower($this->config()['label']) }}
            </flux:button>
        </div>
    </div>

    <flux:card class="mt-6 p-0 overflow-hidden">
        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-6 py-3 dark:border-zinc-700">
            <flux:select wire:model.live="filterEvent" class="w-40">
                <flux:select.option value="">All events</flux:select.option>
                <flux:select.option value="created">Created</flux:select.option>
                <flux:select.option value="updated">Updated</flux:select.option>
                <flux:select.option value="deleted">Deleted</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="perPage" class="w-28">
                <flux:select.option value="25">25 / page</flux:select.option>
                <flux:select.option value="50">50 / page</flux:select.option>
                <flux:select.option value="100">100 / page</flux:select.option>
            </flux:select>
        </div>

        {{-- Change documents: a timestamped header per change, with the
             changed fields tabulated below (SAP-style change document). --}}
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($this->activities as $activity)
                @php
                    $eventColor = match ($activity->event) {
                        'created' => 'green',
                        'updated' => 'blue',
                        'deleted' => 'red',
                        default   => 'zinc',
                    };
                    $newVals = $activity->attribute_changes?->get('attributes') ?? [];
                @endphp
                <div wire:key="activity-{{ $activity->id }}">
                    {{-- Header band: when · event ........................ who --}}
                    <div class="flex items-center justify-between gap-3 bg-zinc-50 px-6 py-2 dark:bg-zinc-800/60">
                        <div class="flex items-center gap-3">
                            <flux:tooltip :content="$activity->created_at->format('d M Y, H:i:s')">
                                <span class="text-[13px] font-medium text-ink tabular-nums cursor-default">
                                    {{ $activity->created_at->format('d M Y, H:i') }}
                                </span>
                            </flux:tooltip>
                            <flux:badge :color="$eventColor" size="sm">{{ ucfirst($activity->event ?? 'unknown') }}</flux:badge>
                        </div>
                        <span class="text-[12.5px] text-ink-3">{{ $activity->causer?->name ?? $activity->getProperty('source') ?? 'System' }}</span>
                    </div>

                    {{-- Changed values --}}
                    <div class="px-6 py-3">
                        @if (!empty($newVals))
                            <div class="overflow-x-auto">
                                <table class="text-[12.5px]">
                                    <thead>
                                        <tr class="border-b border-zinc-100 text-left text-ink-4 dark:border-zinc-800">
                                            @foreach ($newVals as $field => $newVal)
                                                <th class="whitespace-nowrap py-1 pr-6 font-medium">{{ str_replace('_', ' ', $field) }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            @foreach ($newVals as $field => $newVal)
                                                <td class="whitespace-nowrap py-1 pr-6 align-top text-ink">{{ $this->formatValue($field, $newVal) }}</td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @elseif ($activity->event === 'deleted')
                            <span class="text-[12.5px] text-ink-3 italic">Record deleted</span>
                        @elseif ($activity->event === 'created')
                            <span class="text-[12.5px] text-ink-3 italic">Record created</span>
                        @else
                            <span class="text-[12.5px] text-ink-4">—</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-16 text-center">
                    <flux:icon.clock variant="outline" class="mx-auto size-8 text-ink-4" />
                    <div class="mt-3 text-[14px] text-ink-3">No activity recorded for this record yet.</div>
                </div>
            @endforelse
        </div>

        @if ($this->activities->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $this->activities->links() }}
            </div>
        @endif
    </flux:card>
</div>
