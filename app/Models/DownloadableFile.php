<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['product_id', 'name', 'file_path', 'file_name', 'mime_type', 'file_size', 'download_limit', 'download_expiry_days', 'version', 'sort_order'])]
class DownloadableFile extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'download_limit' => 'integer',
            'download_expiry_days' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
