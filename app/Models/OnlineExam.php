<?php

namespace App\Models;


use App\Repositories\Semester\SemesterInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\DateFormatTrait;
use App\Services\CachingService;


class OnlineExam extends Model
{
    use HasFactory, DateFormatTrait;
    use SoftDeletes;

    protected $fillable = [
        'class_section_id',
        'class_subject_id',
        'title',
        'exam_key',
        'duration',
        'start_date',
        'end_date',
        'session_year_id',
        'school_id'
    ];

    protected $appends = ['class_section_with_medium', 'subject_with_name', 'total_marks', 'exam_status_name', 'start_date_iso', 'end_date_iso'];


    public function class_section()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id')->with('class', 'class.shift', 'section', 'medium')->withTrashed();
    }

    public function class_subject()
    {
        return $this->belongsTo(ClassSubject::class, 'class_subject_id');
    }

    public function question_choice()
    {
        return $this->hasMany(OnlineExamQuestionChoice::class, 'online_exam_id');
    }

    public function student_attempt()
    {
        return $this->hasMany(StudentOnlineExamStatus::class, 'online_exam_id');
    }

    public function student_answers()
    {
        return $this->hasMany(OnlineExamStudentAnswer::class, 'online_exam_id');
    }

    public function online_exam_commons()
    {
        return $this->hasMany(OnlineExamCommon::class, 'online_exam_id');
    }

    public function scopeOwner($query)
    {
        if (Auth::user()) {

            if (Auth::user()->hasRole('Super Admin')) {
                return $query;
            }

            $sessionYearId = app(CachingService::class)->getSessionYear()->id;
            if (Auth::user()->hasRole('School Admin')) {
                return $query->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
            }

            if (Auth::user()->hasRole('Teacher')) {
                // $subjectTeacherData = SubjectTeacher::where('teacher_id',Auth::user()->id)->get();
                // $classSubjectIds = $subjectTeacherData->pluck('class_subject_id');
                // return $query->whereIn('class_subject_id',$classSubjectIds)->where('school_id', Auth::user()->school_id);

                $teacherId = Auth::user()->id;
                return $query->whereHas('subject_teacher', function ($query) use ($teacherId, $sessionYearId) {
                    $query->where(['teacher_id' => $teacherId, 'session_year_id' => $sessionYearId])
                        ->whereColumn('class_section_id', 'class_section_id');
                })->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
            }

            if (Auth::user()->hasRole('Student')) {
                $studentAuth = Auth::user()->student;
                $class_subject_ids = $studentAuth->selectedStudentSubjects()->pluck('class_subject_id');
                return $query->where(['school_id' => Auth::user()->school_id])->whereIn('class_subject_id', $class_subject_ids);
            }

            if (Auth::user()->hasRole('Guardian')) {
                return $query;
            }

            if (Auth::user()->school_id) {
                return $query->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
            }
        }

        return $query;
    }

    public function scopeCurrentSemesterData($query)
    {
        $currentSemester = app(SemesterInterface::class)->default();
        if ($currentSemester) {
            $query->where(function ($query) use ($currentSemester) {
                $query->where('semester_id', $currentSemester->id)->orWhereNull('semester_id');
            });
        }
    }

    public function getClassSectionWithMediumAttribute()
    {
        if ($this->relationLoaded('class_section') && $this->class_section) {
            $shiftName = ($this->class_section->class->shift ?? null) ? ' (' . $this->class_section->class->shift->name . ')' : '';
            return $this->class_section->class->name . ' ' . ($this->class_section->section?->name ?? '') . ' - ' . $this->class_section->medium->name . $shiftName;
        }
        return null;
    }

    public function getSubjectWithNameAttribute()
    {
        if ($this->relationLoaded('class_subject')) {
            return $this->class_subject->subject->name . ' - ' . $this->class_subject->subject->type;
        }
        return null;
    }

    public function getTotalMarksAttribute()
    {
        if ($this->relationLoaded('question_choice')) {
            return $this->question_choice->where('online_exam_id', $this->id)->sum('marks');
        }
        return null;
    }

    public function getExamStatusNameAttribute()
    {
        $now = now();

        $startDateTime = $this->getRawOriginal('start_date') ? Carbon::parse($this->getRawOriginal('start_date')) : null;
        $endDateTime = $this->getRawOriginal('end_date') ? Carbon::parse($this->getRawOriginal('end_date')) : null;

        if ($startDateTime && $endDateTime && $now->between($startDateTime, $endDateTime)) {
            return 'On Going';
        }

        if ($startDateTime && $startDateTime->isFuture()) {
            return 'Upcoming';
        }

        return 'Completed';
    }

    /**
     * Get all of the subject_teacher for the Assignment
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function subject_teacher()
    {
        return $this->belongsTo(SubjectTeacher::class, 'class_subject_id', 'class_subject_id');
    }

    public function getStartDateAttribute($value)
    {
        return $this->formatDateValue($value);
    }

    public function getEndDateAttribute($value)
    {
        return $this->formatDateValue($value);
    }

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }

    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }

    public function getStartDateIsoAttribute()
    {
        return Carbon::parse($this->getRawOriginal('start_date'))->format('Y-m-d H:i:s');
    }

    public function getEndDateIsoAttribute()
    {
        return Carbon::parse($this->getRawOriginal('end_date'))->format('Y-m-d H:i:s');
    }
}
