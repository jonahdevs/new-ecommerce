<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SapSyncLog extends Model
{
    protected $fillable = [
        'order_id',
        'operation',
        'status',
        'endpoint',
        'http_method',
        'request_payload',
        'response_payload',
        'http_status_code',
        'error_message',
        'error_trace',
        'sap_document_number',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'request_payload'  => 'array',
            'response_payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
