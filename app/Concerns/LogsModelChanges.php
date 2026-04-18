<?php

namespace App\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Trait for models that need automatic change tracking
 * 
 * This trait configures Spatie Activity Log to track model changes
 * with sensible defaults for e-commerce models.
 * 
 * Usage:
 * 1. Add trait to model: use LogsModelChanges;
 * 2. Implement getLoggedAttributes() to specify which fields to track
 * 3. Optionally override getLogName() for custom log categorization
 * 
 * @see https://spatie.be/docs/laravel-activitylog
 */
trait LogsModelChanges
{
    use LogsActivity;

    /**
     * Get the attributes that should be logged when changed.
     * 
     * Models must implement this method to specify which fields
     * should be tracked in the activity log.
     * 
     * @return array<int, string> Array of attribute names to track
     */
    abstract protected function getLoggedAttributes(): array;

    /**
     * Configure activity log options for this model.
     * 
     * Configures Spatie Activity Log to:
     * - Log only the attributes specified in getLoggedAttributes()
     * - Log only dirty (changed) attributes
     * - Skip empty log submissions (no changes)
     * - Use the log name from getLogName()
     * 
     * @return LogOptions
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
     * Get the log name (category) for this model's activity logs.
     * 
     * Defaults to the lowercase class name (e.g., "product", "order").
     * Override this method in your model for custom log categorization.
     * 
     * @return string
     */
    protected function getLogName(): string
    {
        return strtolower(class_basename($this));
    }
}
