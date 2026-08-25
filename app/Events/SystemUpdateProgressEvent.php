<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemUpdateProgressEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $runId,
        public readonly string $type,
        public readonly array $data
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("system-update.{$this->runId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'system.update.progress';
    }

    public function broadcastWith(): array
    {
        return [
            'run_id' => $this->runId,
            'type' => $this->type,
            'data' => $this->data,
        ];
    }
}
