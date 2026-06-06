<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'operation',
    'status',
    'endpoint',
    'http_method',
    'request_payload',
    'response_payload',
    'http_status_code',
    'error_message',
    'sap_document_number',
    'duration_ms',
])]
class SapSyncLog extends Model
{
    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'http_status_code' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
