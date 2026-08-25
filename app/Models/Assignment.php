<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Traits\DateFormatTrait;
use App\Services\CachingService;
use Carbon\Carbon;

/**
 * @mixin Builder
 */
class Assignment extends Model
{
    use HasFactory, DateFormatTrait;

    protected $fillable = [
        'class_section_id',
        'class_subject_id',
        'name',
        'instructions',
        'due_date',
        'points',
        'resubmission',
        'extra_days_for_resubmission',
        'session_year_id',
        'semester_id',
        'school_id',
        'created_by',
        'edited_by'
    ];
    protected $appends = ['created_by_teacher', 'edited_by_teacher', 'due_date_original', 'created_at_original'];

    protected static function boot()
    {
        parent::boot();
        static::deleting(static function ($assignment) { // before delete() method call this
            //Deletes all the Assignment Submissions first
            $assignment_submission = AssignmentSubmission::where('assignment_id', $assignment->id)->get();
            if ($assignment_submission) {
                foreach ($assignment_submission as $submission) {
                    if (isset($submission->file)) {
                        foreach ($submission->file as $file) {
                            if (Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                                Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                            }
                        }
                        $submission->delete();
                    }
                }
            }

            //After that Delete Assignment and its files from the server
            if ($assignment->file) {
                foreach ($assignment->file as $file) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }
                }
            }
            $assignment->file()->delete();
        });
    }

    public function class_subject()
    {
        return $this->belongsTo(ClassSubject::class)->withTrashed();
    }

    public function submission()
    {
        return $this->hasOne(AssignmentSubmission::class);
    }

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class)->with('class', 'section')->withTrashed();
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class)->withTrashed();
    }

    public function file()
    {
        return $this->morphMany(File::class, 'modal');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function editec()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function assignment_commons()
    {
        return $this->hasMany(AssignmentCommon::class, 'assignment_id');
    }

    public function scopeAssignmentTeachers($query)
    {
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            // TODO: Mahesh teacher_id foreign key directly assigned to user table
            // $teacher_id = $user->teacher()->select('id')->pluck('id')->first();
            // $subject_teacher = SubjectTeacher::select('class_section_id', 'subject_id')->where('teacher_id', $teacher_id)->get();
            $subject_teacher = SubjectTeacher::select(['class_section_id', 'subject_id'])->where('teacher_id', Auth::user()->id)->get();
            if ($subject_teacher) {
                $subject_teacher = $subject_teacher->toArray();
                $class_section_id = array_column($subject_teacher, 'class_section_id');
                $subject_id = array_column($subject_teacher, 'subject_id');
                return $query->whereIn('class_section_id', $class_section_id)->whereIn('subject_id', $subject_id);
            }
            return $query;
        }
        return $query;
    }

    public function scopeOwner($query)
    {
        if (Auth::user()) {
            $sessionYearId = app(CachingService::class)->getSessionYear()->id;
            if (Auth::user()->hasRole('Super Admin')) {
                return $query;
            }

            if (Auth::user()->hasRole('School Admin')) {
                return $query->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
            }

            if (Auth::user()->hasRole('Teacher')) {
                $teacherId = Auth::user()->id;
                return $query->whereHas('subject_teacher', function ($query) use ($teacherId, $sessionYearId) {
                    $query->where(['teacher_id' => $teacherId, 'session_year_id' => $sessionYearId])
                        ->whereColumn('class_section_id', 'class_section_id');
                })->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
            }

            if (Auth::user()->hasRole('Student')) {
                $studentAuth = Auth::user()->student;
                $class_subject_ids = $studentAuth->selectedStudentSubjects()->pluck('class_subject_id');
                return $query->whereIn('class_subject_id', $class_subject_ids)->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
            }

            if (Auth::user()->hasRole('Guardian')) {
                $childId = request('child_id');
                $studentAuth = Students::where('id', $childId)->first();
                $class_subject_ids = $studentAuth->selectedStudentSubjects()->pluck('class_subject_id');
                return $query->whereIn('class_subject_id', $class_subject_ids)->where(['school_id' => $studentAuth->school_id, 'session_year_id' => $sessionYearId]);
            }
        }
        return $query;
    }

    public function getCreatedByTeacherAttribute()
    {
        /*TODO : Problematic Code. This might will trigger N+1 Query issue*/
        return $this->belongsTo(User::class, 'created_by')->withTrashed()->first()->full_name ?? NULL;
    }

    public function getEditedByTeacherAttribute()
    {
        /*TODO : Problematic Code. This might will trigger N+1 Query issue*/
        return $this->belongsTo(User::class, 'edited_by')->withTrashed()->first()->full_name ?? NULL;
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

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }

    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }

    public function getDueDateAttribute($value)
    {
        return  $this->formatDateValue($value);
    }

    public function getDueDateOriginalAttribute()
    {
        $dueDate = $this->getRawOriginal('due_date');
        if (!$dueDate) {
            return null;
        }
        return Carbon::parse($dueDate)->format('d-m-Y h:i A');
    }

    public function getCreatedAtOriginalAttribute()
    {
        $created_at = $this->getRawOriginal('created_at');
        if (!$created_at) {
            return null;
        }
        return Carbon::parse($created_at)->format('d-m-Y h:i A');
    }
}
