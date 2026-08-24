<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherOnboardingResult extends Model
{
    protected $fillable = [
        'user_id',
        'score',
        'total_questions',
        'status',
        'school_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
