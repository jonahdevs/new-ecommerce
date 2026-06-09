<?php

namespace App\Models\Concerns;

use App\Models\StatusHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use UnitEnum;

trait HasStatusHistory
{
    public function statusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'historyable')->orderBy('created_at');
    }

    public function recordStatusChange(
        ?UnitEnum $from,
        UnitEnum $to,
        ?string $note = null,
        ?int $changedBy = null,
    ): void {
        $this->statusHistories()->create([
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'note' => $note ?: null,
            'changed_by' => $changedBy,
        ]);
    }
}
