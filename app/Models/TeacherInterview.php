<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherInterview extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'interviewer_id',
        'interview_date',
        'time',
        'location',
        'instructions',
        'status',
        'remarks'
    ];

    public function application()
    {
        return $this->belongsTo(TeacherInterviewApplication::class, 'application_id');
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(TeacherInterviewFeedback::class, 'interview_id');
    }
}
