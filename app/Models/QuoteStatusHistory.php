<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteStatusHistory extends Model
{
    protected $table = 'quote_status_history';

    protected $fillable = [
        'quote_id',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'changed_by_type',
        'notes',
    ];

    // =====================================================
    // Relationships
    // =====================================================

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
