<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // =========================================================================
    //  Broadcast when an order is updated (status change, payment, etc.)
    //  Used for real-time updates in:
    //    - Admin dashboard & orders page (admin.orders channel)
    //    - Customer order pages (order.{orderId} channel)
    //    - Customer orders list (App.Models.User.{userId} channel)
    // =========================================================================

    public function __construct(
        public readonly Order $order,
        public readonly string $updateType = 'general', // status, payment, created, deleted
        public readonly ?int $updatedByUserId = null,
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [
            // Admin channel — for dashboard and orders management
            new PrivateChannel('admin.orders'),
            // Customer channel — for order detail/tracking pages
            new PrivateChannel('order.' . $this->order->id),
        ];

        // Also broadcast to the user's personal channel for orders list updates
        if ($this->order->user_id) {
            $channels[] = new PrivateChannel('App.Models.User.' . $this->order->user_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'reference' => $this->order->reference,
            'status' => $this->order->status->value,
            'status_label' => $this->order->status->label(),
            'status_color' => $this->order->status->color(),
            'payment_status' => $this->order->payment_status?->value,
            'payment_status_label' => $this->order->payment_status?->label(),
            'total' => $this->order->total,
            'customer_name' => $this->order->customerName(),
            'update_type' => $this->updateType,
            'updated_by' => $this->updatedByUserId,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
