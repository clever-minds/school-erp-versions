<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\SessionYearsTracking;
use App\Traits\DateFormatTrait;
use App\Services\CachingService;

class Lesson extends Model
{
    use HasFactory, DateFormatTrait;

    protected $fillable = [
        'name',
        'description',
        'school_id'
    ];

    protected $appends = [];


    protected static function boot()
    {
        parent::boot();
        static::deleting(static function ($lesson) { // before delete() method call this
            if ($lesson->file) {
                foreach ($lesson->file as $file) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }
                    if ($file->file_thumbnail && Storage::disk('public')->exists($file->getRawOriginal('file_thumbnail'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_thumbnail'));
                    }
                }

                $lesson->file()->delete();
            }
            if ($lesson->topic) {
                $lesson->topic()->delete();
            }
        });
    }

    public function scopeOwner($query)
    {
        if (Auth::user()) {
            if (Auth::user()->hasRole('Super Admin')) {
                return $query;
            }

            if (Auth::user()->hasRole('School Admin') || Auth::user()->hasRole('Student')) {
                $sessionYearId = app(CachingService::class)->getSessionYear()->id;
                return $query->where('school_id', Auth::user()->school_id)->whereHas('lesson_commons.syllabus.class_subject', function ($q) use ($sessionYearId) {
                    $q->where('session_year_id', $sessionYearId);
                });
            }

            if (Auth::user()->hasRole('Teacher')) {
                $teacherId = Auth::user()->id;
                $sessionYearId = app(CachingService::class)->getSessionYear()->id;
                $subjectTeacher = SubjectTeacher::where(['teacher_id' => $teacherId, 'session_year_id' => $sessionYearId])->pluck('class_subject_id')->toArray();
                return $query->whereHas('lesson_commons.syllabus.class_subject', function ($query) use ($subjectTeacher) {
                    $query->whereIn('id', $subjectTeacher);
                });
            }
        }

        return $query;
    }

    // public function class_subject()
    // {
    //     return $this->belongsTo(ClassSubject::class);
    // }

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class)->with('class', 'class.shift', 'section', 'medium')->withTrashed();
    }

    public function file()
    {
        return $this->morphMany(File::class, 'modal');
    }

    public function topic()
    {
        return $this->hasMany(LessonTopic::class);
    }

    public function lesson_commons()
    {
        return $this->hasMany(LessonCommon::class);
    }

    public function assignment_commons()
    {
        return $this->hasMany(AssignmentCommon::class, 'assignment_id');
    }

    public function getClassSectionWithMediumAttribute()
    {
        // if ($this->relationLoaded('lesson_commons')) {
        //     if ($this->lesson_commons) {
        //         $firstCommon = $this->lesson_commons()->first();
        //         if ($firstCommon && $firstCommon->class_section) {
        //             $shiftName = ($firstCommon->class_section->class->shift ?? null) ? ' (' . $firstCommon->class_section->class->shift->name . ')' : '';
        //             return $firstCommon->class_section->class->name . ' ' . ($firstCommon->class_section->section?->name ?? '') . ' - ' . $firstCommon->class_section->medium->name . $shiftName;
        //         }
        //     }
        // }
        return null;
    }


    public function getSubjectWithNameAttribute()
    {
        // if ($this->relationLoaded('lesson_commons')) {
        //     if ($this->lesson_commons) {
        //         return $this->lesson_commons()->first()->class_subject?->subject->name . ' - ' . $this->lesson_commons()->first()->class_subject?->subject->type;
        //     }

        // }
        return null;
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

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'id');
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
