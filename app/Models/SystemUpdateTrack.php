<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemUpdateTrack extends Model
{
    protected $fillable = [
        'run_id',
        'school_id',
        'school_name',
        'database_name',
        'status',
        'migration_status',
        'seeder_status',
        'progress',
        'error_message',
        'failed_step',
        'started_at',
        'completed_at',
    ];

    protected $connection = 'mysql';

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->setTable(env('DB_DATABASE') . '.system_update_tracks');
        });
    }

    public function getTable()
    {
        return env('DB_DATABASE') . '.system_update_tracks';
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(SystemUpdateRun::class, 'run_id', 'run_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
