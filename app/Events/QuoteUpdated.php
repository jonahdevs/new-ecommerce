<?php

namespace App\Events;

use App\Models\Quote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // =========================================================================
    //  Broadcast when a quote is updated (status change, pricing, etc.)
    //  Used for real-time updates on customer quotation pages.
    // =========================================================================

    public function __construct(
        public readonly Quote $quote,
        public readonly string $updateType = 'general', // status, pricing, created
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            // Customer channel — for quote owner to receive updates
            new PrivateChannel('quote.' . $this->quote->id),
            // User channel — for general notifications
            new PrivateChannel('App.Models.User.' . $this->quote->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'quote.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'quote_id' => $this->quote->id,
            'reference' => $this->quote->reference,
            'status' => $this->quote->status->value,
            'status_label' => $this->quote->status->label(),
            'status_color' => $this->quote->status->color(),
            'total' => $this->quote->total,
            'update_type' => $this->updateType,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
