<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Trait for models that need automatic change tracking
 * 
 * This trait extends Spatie's LogsActivity with sensible defaults
 * for e-commerce models.
 * 
 * Usage:
 * 1. Add trait to model: use LogsModelChanges;
 * 2. Optionally override getActivitylogOptions() for custom config
 */
trait LogsModelChanges
{
    use LogsActivity;

    /**
     * Default activity log options
     * 
     * Override this method in your model to customize:
     * - Which attributes to log
     * - Which attributes to ignore
     * - Log name
     * - Whether to log only dirty attributes
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getLoggedAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->getLogName());
    }

    /**
     * Get attributes to log
     * 
     * Override in model to specify which attributes to track
     * Default: all fillable attributes
     */
    protected function getLoggedAttributes(): array
    {
        // By default, log all fillable attributes
        return $this->fillable ?? ['*'];
    }

    /**
     * Get log name for this model
     * 
     * Override in model for custom log names
     */
    protected function getLogName(): string
    {
        return strtolower(class_basename($this));
    }

    /**
     * Get description for activity log
     * 
     * Override in model for custom descriptions
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        $modelName = class_basename($this);

        return match ($eventName) {
            'created' => "{$modelName} created",
            'updated' => "{$modelName} updated",
            'deleted' => "{$modelName} deleted",
            'restored' => "{$modelName} restored",
            default => "{$modelName} {$eventName}",
        };
    }
}
