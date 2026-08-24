<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherInterviewCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'status'];

    public function questions()
    {
        return $this->hasMany(TeacherInterviewFeedbackQuestion::class, 'teacher_interview_category_id');
    }
}
