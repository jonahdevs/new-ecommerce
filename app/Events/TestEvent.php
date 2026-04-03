<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $message = 'Hello from server!'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("test.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'test.event';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'time' => now()->toTimeString(),
        ];
    }
}
