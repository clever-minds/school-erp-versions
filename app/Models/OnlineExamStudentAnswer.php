<?php

namespace App\Models;

use App\Repositories\Student\StudentInterface;
use App\Repositories\SubjectTeacher\SubjectTeacherInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\DateFormatTrait;
use App\Services\CachingService;


class OnlineExamStudentAnswer extends Model
{
    use HasFactory, DateFormatTrait;
    protected $fillable = [
        'student_id',
        'online_exam_id',
        'question_id',
        'option_id',
        'submitted_date',
        'school_id'
    ];

    public function online_exam()
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id')->withTrashed();
    }

    public function user_submitted_questions(){
        return $this->belongsTo(OnlineExamQuestionChoice::class,'question_id');
    }

    public function scopeOwner($query)
    {
        if (Auth::user()) {
            if (Auth::user()->hasRole('Super Admin')) {
                return $query;
            }
    
            if (Auth::user()->hasRole('School Admin')) {
                $sessionYearId = app(CachingService::class)->getSessionYear()->id;
                return $query->where('school_id', Auth::user()->school_id)->whereHas('online_exam', function($q) use($sessionYearId) {
                    $q->where('session_year_id', $sessionYearId);
                });
            }
    
            if(Auth::user()->hasRole('Teacher')) {
                $sessionYearId = app(CachingService::class)->getSessionYear()->id;
                $subjectTeacher = app(SubjectTeacherInterface::class);
                $student = app(StudentInterface::class);
                $teacherId = Auth::user()->id;
                $classSectionId = $subjectTeacher->builder()->where(['teacher_id' => $teacherId, 'session_year_id' => $sessionYearId])->pluck('class_section_id');
                $studentId = $student->builder()->whereIn('class_section_id', $classSectionId)->pluck('user_id');
                return $query->whereIn('student_id',$studentId)->where('school_id', Auth::user()->school_id);
            }
    
            if (Auth::user()->hasRole('Student')) {
                $sessionYearId = app(CachingService::class)->getSessionYear()->id;
                return $query->where('school_id', Auth::user()->school_id)->whereHas('online_exam', function($q) use($sessionYearId) {
                    $q->where('session_year_id', $sessionYearId);
                });
            }
        }

        return $query;
    }

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }
    
    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }
    

}
