<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LessonCommon extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'lesson_id',
        'class_section_id',
        'class_subject_id',
    ];


    public function scopeOwner($query) {
        if (Auth::user()->hasRole('Super Admin')) {
            return $query;
        }

        if (Auth::user()->hasRole('School Admin')) {
            return $query->where('school_id', Auth::user()->school_id);
        }

        if (Auth::user()->hasRole('Teacher')) {
            $teacherId = Auth::user()->id;
            return $query->whereHas('subject_teacher', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId)
                      ->whereColumn('class_section_id', 'lessons.class_section_id');
            })->where('school_id',Auth::user()->school_id);
            return $query->where('school_id', Auth::user()->school_id);
        }

        if (Auth::user()->hasRole('Student')) {
            return $query->where('school_id', Auth::user()->school_id);
        }

        return $query;
    }
  
    public function subject_teacher()
    {
        return $this->belongsTo(SubjectTeacher::class, 'class_subject_id','class_subject_id');
    }

}
