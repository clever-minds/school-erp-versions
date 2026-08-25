<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $channel,
        public string $event,
        public mixed $message,
        public mixed $from = null,
        public mixed $to = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel($this->channel)];
    }

    public function broadcastAs(): string
    {
        return $this->event;
    }

    public function broadcastWith(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'message' => $this->message,
        ];
    }
}
