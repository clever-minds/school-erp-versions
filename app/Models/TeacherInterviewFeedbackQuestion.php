<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherInterviewFeedbackQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'feedback_question',
        'teacher_interview_category_id',
        'status',
        'type',
        'custom_options',
        'audit_option_group_id'
    ];

    public function category()
    {
        return $this->belongsTo(TeacherInterviewCategory::class, 'teacher_interview_category_id');
    }

    public function optionGroup()
    {
        return $this->belongsTo(AuditOptionGroup::class, 'audit_option_group_id');
    }
}
