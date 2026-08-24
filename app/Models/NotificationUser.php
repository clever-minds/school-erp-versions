<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationUser extends Model
{
    use HasFactory;

    protected $table = 'notification_users';

    protected $fillable = [
        'notification_id',
        'user_id',
        'user_role',
        'student_id',
        'is_read',
        'sent_at',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Belongs to Notification
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Belongs to User (Parent)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Belongs to Student (optional)
     */
    public function student()
    {
        return $this->belongsTo(User::class);
    }
}
