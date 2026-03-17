<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductDownload extends Model
{
    protected $fillable = [
        'product_id',
        'variant_id',
        'name',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'download_limit',
        'download_expiry',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'file_size'        => 'integer',
            'download_limit'   => 'integer',
            'download_expiry'  => 'integer',
            'sort_order'       => 'integer',
        ];
    }

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // ===============================================
    // ACCESSORS
    // ===============================================

    /**
     * Get the full download URL
     */
    protected function downloadUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->file_path
                ? Storage::disk('private')->url($this->file_path)
                : null,
        );
    }

    /**
     * Get human-readable file size
     */
    protected function formattedFileSize(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->file_size) return null;

                $units = ['B', 'KB', 'MB', 'GB'];
                $size = $this->file_size;
                $unit = 0;

                while ($size >= 1024 && $unit < count($units) - 1) {
                    $size /= 1024;
                    $unit++;
                }

                return round($size, 2) . ' ' . $units[$unit];
            }
        );
    }

    /**
     * Get file type icon based on extension
     */
    protected function fileIcon(): Attribute
    {
        return Attribute::make(
            get: fn() => match (strtolower($this->file_type ?? '')) {
                'pdf'              => 'document-text',
                'zip', 'rar', '7z' => 'archive-box',
                'xls', 'xlsx'      => 'table-cells',
                'doc', 'docx'      => 'document',
                'jpg', 'jpeg',
                'png', 'gif'       => 'photo',
                default            => 'document',
            }
        );
    }

    // ===============================================
    // METHODS
    // ===============================================

    /**
     * Check if download has expired for a given purchase date
     */
    public function isExpiredFor(\Carbon\Carbon $purchaseDate): bool
    {
        if (!$this->download_expiry) return false;

        return $purchaseDate->addDays($this->download_expiry)->isPast();
    }

    /**
     * Check if download limit has been reached
     */
    public function isLimitReached(int $downloadCount): bool
    {
        if (!$this->download_limit) return false;

        return $downloadCount >= $this->download_limit;
    }
}
