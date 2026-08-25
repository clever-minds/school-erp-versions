<?php

namespace App\Models;

use App\Services\CachingService;
use App\Traits\DateFormatTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory, DateFormatTrait;

    protected $fillable = [
        'title',
        'description',
        'due_date',
        'user_id',
        'status',
        'assigned_by',
        'is_admin_assigned',
    ];

    protected $connection = 'school';
    protected $appends = ['overdue_days', 'date_format', 'completed_date'];

    protected $casts = [
        'due_date' => 'date',
        'is_admin_assigned' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCreatedAtAttribute($value)
    {
        return $this->formatDateValue($value);
    }

    public function getUpdatedAtAttribute($value)
    {
        return $this->formatDateValue($value);
    }

    public function getDueDateAttribute($value)
    {
        return $this->formatDateOnly($value);
    }

    public function getOverdueDaysAttribute()
    {
        if ($this->status == 'completed') {
            return now()->diffInDays($this->getRawOriginal('updated_at'));
        }
        // first need to check if due date has passed or not
        if ($this->getRawOriginal('due_date') > now()->format('Y-m-d')) {
            return 0;
        }
        return now()->diffInDays($this->getRawOriginal('due_date'));
    }

    public function getDateFormatAttribute()
    {
        return date('Y-m-d', strtotime($this->getRawOriginal('due_date')));
    }

    public function getStatusAttribute()
    {
        $value = $this->attributes['status'];
        // If status pending and due date is passed then set status to overdue
        if ($value == 'pending' && $this->getRawOriginal('due_date') < now()->format('Y-m-d')) {
            return 'overdue';
        } else {
            return $value;
        }
    }

    public function getCompletedDateAttribute()
    {
        if ($this->status == 'completed') {
            return date('Y-m-d', strtotime($this->getRawOriginal('updated_at')));
        }
        return null;
    }
}
