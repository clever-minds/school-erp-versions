<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SchoolProvisionProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int    $schoolId,
        public readonly string $schoolName,
        public readonly string $step,
        public readonly string $stepLabel,
        public readonly int    $progress,
        public readonly string $status,   // 'installing' | 'completed' | 'failed'
        public readonly string $message = ''
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("school.{$this->schoolId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'school.provision.progress';
    }

    public function broadcastWith(): array
    {
        return [
            'school_id'   => $this->schoolId,
            'school_name' => $this->schoolName,
            'step'        => $this->step,
            'step_label'  => $this->stepLabel,
            'progress'    => $this->progress,
            'status'      => $this->status,
            'message'     => $this->message,
        ];
    }
}
