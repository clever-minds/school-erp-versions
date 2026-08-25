<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemUpdateRun extends Model
{
    protected $fillable = [
        'run_id',
        'version_from',
        'version_to',
        'status',
        'total_schools',
        'completed_schools',
        'failed_schools',
        'log',
        'started_at',
        'completed_at',
    ];

    protected $connection = 'mysql';

    protected $casts = [
        'log' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->setTable(env('DB_DATABASE') . '.system_update_runs');
        });
    }

    public function getTable()
    {
        return env('DB_DATABASE') . '.system_update_runs';
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(SystemUpdateTrack::class, 'run_id', 'run_id');
    }

    /**
     * Append a timestamped log entry.
     */
    public function appendLog(string $message): void
    {
        $logs = $this->log ?? [];
        $logs[] = [
            'time' => now()->format('H:i:s'),
            'message' => $message,
        ];
        $this->update(['log' => $logs]);
    }
}
