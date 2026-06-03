<?php

namespace App\Support;

use Closure;
use Spatie\Activitylog\Models\Activity;

/**
 * Process-scoped label for the origin of model changes that happen without an
 * authenticated user (queued jobs, console commands, the SAP sync). When set,
 * it is stamped onto each {@see Activity} so the
 * audit trail can show "SAP sync" instead of a blank causer.
 */
class ActivitySource
{
    private static ?string $current = null;

    public static function current(): ?string
    {
        return self::$current;
    }

    /**
     * Run a callback with every activity it triggers attributed to $label.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public static function for(string $label, Closure $callback): mixed
    {
        $previous = self::$current;
        self::$current = $label;

        try {
            return $callback();
        } finally {
            self::$current = $previous;
        }
    }
}
