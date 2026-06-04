<?php

namespace App\Models;

use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['email', 'interests', 'token', 'source', 'ip_address', 'subscribed_at', 'unsubscribed_at'])]
class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public static function booted(): void
    {
        static::creating(function (Subscriber $subscriber) {
            $subscriber->token ??= Str::random(64);
        });
    }

    // Scopes

    public function scopeConfirmed(Builder $query): void
    {
        $query->whereNotNull('subscribed_at')->whereNull('unsubscribed_at');
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNull('subscribed_at')->whereNull('unsubscribed_at');
    }

    public function scopeUnsubscribed(Builder $query): void
    {
        $query->whereNotNull('unsubscribed_at');
    }

    // Helpers

    public function isConfirmed(): bool
    {
        return $this->subscribed_at !== null && $this->unsubscribed_at === null;
    }

    public function isPending(): bool
    {
        return $this->subscribed_at === null && $this->unsubscribed_at === null;
    }

    public function isUnsubscribed(): bool
    {
        return $this->unsubscribed_at !== null;
    }

    public function confirmationUrl(): string
    {
        return route('newsletter.confirm', $this->token);
    }

    public function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', $this->token);
    }
}
