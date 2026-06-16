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

#[Fillable([
    'order_id',
    'provider',
    'status',
    'amount_cents',
    'currency',
    'phone',
    'account_reference',
    'merchant_request_id',
    'checkout_request_id',
    'mpesa_receipt',
    'result_code',
    'result_desc',
    'stripe_payment_intent_id',
    'stripe_charge_id',
    'card_brand',
    'card_last4',
    'paystack_reference',
    'channel',
    'authorization_code',
    'refund_cents',
    'refunded_at',
    'payload',
    'paid_at',
])]
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
     * Short-lived Paystack access code returned by Initialize Transaction. Like
     * the Stripe client secret it is request-scoped and never persisted — it is
     * only used to resume the inline popup on the current page load.
     */
    protected ?string $transientAccessCode = null;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount_cents', 'provider', 'paid_at', 'refunded_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('payment');
    }

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount_cents' => 'integer',
            'refund_cents' => 'integer',
            'result_code' => 'integer',
            // Encrypted at rest: the raw gateway payload holds PII (name, email,
            // phone, IP, masked card) governed by Kenya's DPA 2019 (s.41).
            'payload' => 'encrypted:array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    // ==================================================
    // RELATIONSHIPS
    // ==================================================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ==================================================
    // ACCESSORS
    // ==================================================

    public function getStripeClientSecretAttribute(): ?string
    {
        return $this->transientClientSecret;
    }

    public function getPaystackAccessCodeAttribute(): ?string
    {
        return $this->transientAccessCode;
    }

    // ==================================================
    // HELPERS
    // ==================================================

    /**
     * Attach the (non-persisted) Stripe client secret for this request.
     */
    public function withStripeClientSecret(?string $secret): static
    {
        $this->transientClientSecret = $secret;

        return $this;
    }

    /**
     * Attach the (non-persisted) Paystack access code for this request.
     */
    public function withPaystackAccessCode(?string $accessCode): static
    {
        $this->transientAccessCode = $accessCode;

        return $this;
    }

    /**
     * Human-friendly payment method actually used. Paystack and Stripe are only
     * gateways, so the meaningful method is the settlement channel the customer
     * paid through — card, M-Pesa / mobile money, bank transfer… — captured on
     * the verified transaction. Falls back to the provider when no channel was
     * recorded (e.g. a direct Daraja M-Pesa payment).
     */
    public function methodLabel(): string
    {
        return match ($this->channel) {
            'card' => 'Card',
            'mobile_money', 'mpesa' => 'M-Pesa / Mobile money',
            'airtel' => 'Airtel Money',
            'bank', 'bank_transfer' => 'Bank transfer',
            'ussd' => 'USSD',
            'qr' => 'QR',
            default => match ($this->provider) {
                'mpesa' => 'M-Pesa',
                'stripe' => 'Card',
                'paystack' => 'Paystack',
                default => ucwords(str_replace('_', ' ', (string) $this->provider)),
            },
        };
    }
}
