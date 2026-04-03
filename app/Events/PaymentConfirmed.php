<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.``
     */
    public function __construct(
        public readonly Order $order,
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("order.{$this->order->id}"),
        ];
    }

    /**
     * Only broadcast the data the frontend needs.
     * Never expose sensitive payment data over the wire.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'status' => $this->order->payment?->status,
        ];
    }
}
