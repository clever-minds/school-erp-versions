<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    // Temporary storage for old data (not saved to DB)
    protected $oldAttributesData = [];

    public static function bootLogsActivity()
    {
        static::updating(function ($model) {
            $model->oldAttributesData = $model->getOriginal();
        });

        static::updated(function ($model) {
            $old = $model->oldAttributesData ?? [];
            $new = $model->getChanges();

            $changes = [
                'old' => $old,
                'new' => $new,
            ];

            self::storeActivity($model, 'Update', $changes);
        });

        static::created(function ($model) {
            $changes = [
                'old' => [],
                'new' => $model->getAttributes(),
            ];

            self::storeActivity($model, 'Create', $changes);
        });

        static::deleted(function ($model) {
            $changes = [
                'old' => $model->getOriginal(),
                'new' => [],
            ];

            self::storeActivity($model, 'Delete', $changes);
        });

    }

    protected static function storeActivity($model, $action, $changes = null)
    {
        ActivityLog::create([
            'user_id'    => Auth::id(),
            'model_name' => class_basename($model),
            'action'     => $action,
            'record_id'  => $model->id ?? null,
            'changes'    => $changes ? json_encode($changes, JSON_PRETTY_PRINT) : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ]);
    }
}
