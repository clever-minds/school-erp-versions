<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcademySetupProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $schoolId,
        public readonly string $runId,
        public readonly array $progress
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("academy-setup.{$this->schoolId}.{$this->runId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'academy.setup.progress';
    }

    public function broadcastWith(): array
    {
        return [
            'run_id' => $this->runId,
            'progress' => $this->progress,
        ];
    }
}
