<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherInterviewFeedback extends Model
{
    use HasFactory;

    protected $table = 'teacher_interview_feedbacks';

    protected $fillable = [
        'interview_id',
        'question_id',
        'interviewer_feedback'
    ];

    public function interview()
    {
        return $this->belongsTo(TeacherInterview::class, 'interview_id');
    }

    public function question()
    {
        return $this->belongsTo(TeacherInterviewFeedbackQuestion::class, 'question_id');
    }
}
