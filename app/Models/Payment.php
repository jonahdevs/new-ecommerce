<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['order_id', 'provider', 'status', 'amount_cents', 'phone', 'account_reference', 'merchant_request_id', 'checkout_request_id', 'stripe_session_id', 'stripe_payment_intent_id', 'mpesa_receipt', 'result_code', 'result_desc', 'payload', 'paid_at'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, LogsActivity;

    /**
     * Short-lived Stripe client secret. Per the payments migration this is a
     * credential that must live in session/memory only and is never persisted,
     * so it's held on the in-memory model for the current request only.
     */
    protected ?string $transientClientSecret = null;

    /**
     * Attach the (non-persisted) Stripe client secret for this request.
     */
    public function withStripeClientSecret(?string $secret): static
    {
        $this->transientClientSecret = $secret;

        return $this;
    }

    public function getStripeClientSecretAttribute(): ?string
    {
        return $this->transientClientSecret;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount_cents', 'provider', 'paid_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('payment');
    }

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount_cents' => 'integer',
            'result_code' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::SUCCESS;
    }
}
