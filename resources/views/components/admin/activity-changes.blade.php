@props(['activity', 'logName'])

@php
    $newVals = $activity->attribute_changes?->get('attributes') ?? [];
    $oldVals = $activity->attribute_changes?->get('old') ?? [];
@endphp

@if ($activity->event === 'created')
    <span class="text-[12.5px] text-ink-3 italic">Record created</span>
@elseif ($activity->event === 'deleted')
    <span class="text-[12.5px] text-ink-3 italic">Record deleted</span>
@elseif (!empty($newVals))
    <div class="space-y-0.5">
        @foreach ($newVals as $field => $newVal)
            @php $oldVal = $oldVals[$field] ?? null; @endphp
            <div class="flex flex-wrap items-center gap-1 text-[12px]">
                <span class="font-mono text-ink-3">{{ str_replace('_', ' ', $field) }}</span>
                <span class="text-ink-4">·</span>
                @if ($oldVal !== null)
                    <span class="rounded bg-red-50 px-1 text-red-600 line-through">
                        {{ \App\Support\ActivityLog::formatValue($logName, $field, $oldVal) }}
                    </span>
                    <flux:icon.arrow-right variant="micro" class="size-3 text-ink-4" />
                @endif
                <span class="rounded bg-emerald-50 px-1 text-emerald-700">
                    {{ \App\Support\ActivityLog::formatValue($logName, $field, $newVal) }}
                </span>
            </div>
        @endforeach
    </div>
@else
    <span class="text-[12.5px] text-ink-4">—</span>
@endif
