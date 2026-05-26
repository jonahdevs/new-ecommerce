<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['downloadable_file_id', 'order_id', 'user_id', 'token', 'downloads_remaining', 'expires_at', 'download_count'])]
class OrderDownload extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'downloads_remaining' => 'integer',
            'download_count' => 'integer',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function downloadableFile(): BelongsTo
    {
        return $this->belongsTo(DownloadableFile::class);
    }
}
