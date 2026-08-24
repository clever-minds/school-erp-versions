<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyAcknowledgment extends Model
{
    protected $table = 'staff_policy_acknowledgments';

    protected $fillable = [
        'staff_id',
        'policy_id',
        'school_id',
        'acknowledged_at'
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime'
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function policy()
    {
        return $this->belongsTo(SchoolPolicy::class);
    }
}
