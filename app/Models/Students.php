<?php

namespace App\Models;

use App\Repositories\StudentSubject\StudentSubjectInterface;
use App\Services\CachingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\DateFormatTrait;

class Students extends Model
{
    use SoftDeletes;
    use HasFactory, DateFormatTrait;

    protected $fillable = [
        'user_id',
        'class_id',
        'class_section_id',
        'admission_no',
        'roll_number',
        'admission_date',
        'guardian_id',
        'school_id',
        'session_year_id',
        'application_type',
        'application_status',
        'join_session_year_id',
        'leave_session_year_id'
    ];
    protected $appends = ['first_name', 'last_name', 'full_name'];

    public function scopeOwner($query)
    {
        if (Auth::user()) {
            // Super Admin does not have session year concept
            if (Auth::user()->hasRole('Super Admin')) {
                return $query;
            }

            $sessionYearId = app(CachingService::class)->getSessionYear()->id;

            // Use addSelect with a subquery to fetch historical class section without a top-level JOIN.
            // This is "Ambiguity-Proof" and ensures that external filters on 'class_section_id' target the base table.
            $query->addSelect([
                'historical_class_section_id' => PromoteStudent::select('class_section_id')
                    ->whereColumn('student_id', 'students.user_id')
                    ->where('session_year_id', $sessionYearId)
                    ->limit(1),
                'historical_roll_number' => PromoteStudent::select('roll_number') // ADD THIS
                    ->whereColumn('student_id', 'students.user_id')
                    ->where('session_year_id', $sessionYearId)
                    ->limit(1)
            ]);

            // Handle session_year_id filtering via Existence checks (Subqueries)
            $query->where(function ($q) use ($sessionYearId) {
                // Scenario A: Historical record exists for this session
                $q->whereExists(function ($sub) use ($sessionYearId) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('promote_students')
                        ->whereColumn('promote_students.student_id', 'students.user_id')
                        ->where('promote_students.session_year_id', $sessionYearId);
                })
                    // Scenario B: No historical record exists, so use current session data
                    ->orWhere(function ($q) use ($sessionYearId) {
                        $q->whereNotExists(function ($sub) use ($sessionYearId) {
                            $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                                ->from('promote_students')
                                ->whereColumn('promote_students.student_id', 'students.user_id')
                                ->where('promote_students.session_year_id', $sessionYearId);
                        })->where('students.session_year_id', $sessionYearId);
                    });
            });

            if (Auth::user()->hasRole('Teacher')) {
                // Teacher's viewable class sections should also be session-aware
                $classSectionID = ClassTeacher::where(['teacher_id' => Auth::user()->id, 'session_year_id' => $sessionYearId])->pluck('class_section_id')->toArray();
                $subjectTeachers = SubjectTeacher::where(['teacher_id' => Auth::user()->id, 'session_year_id' => $sessionYearId])->pluck('class_section_id')->toArray();
                $class_section_ids = array_merge($classSectionID, $subjectTeachers);

                // Filtering based on either current or historical enrollment without using a top-level COALESCE/Join
                return $query->where(function ($q) use ($sessionYearId, $class_section_ids) {
                    $q->whereIn('students.class_section_id', $class_section_ids)
                        ->whereNotExists(function ($sub) use ($sessionYearId) {
                            $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                                ->from('promote_students')
                                ->whereColumn('promote_students.student_id', 'students.user_id')
                                ->where('promote_students.session_year_id', $sessionYearId);
                        });

                    $q->orWhereExists(function ($sub) use ($sessionYearId, $class_section_ids) {
                        $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('promote_students')
                            ->whereColumn('promote_students.student_id', 'students.user_id')
                            ->where('promote_students.session_year_id', $sessionYearId)
                            ->whereIn('promote_students.class_section_id', $class_section_ids);
                    });
                });
            }

            if (Auth::user()->hasRole('Guardian')) {
                $childId = request('child_id');
                if ($childId) {
                    $query = $query->where('id', $childId);
                }
                return $query;
            }

            if (Auth::user()->school_id || Auth::user()->hasRole('Student') || Auth::user()->hasRole('School Admin')) {
                return $query->where('students.school_id', Auth::user()->school_id);
            }
        }

        return $query;
    }

    /**
     * Scope a query to include both current and historical class section records.
     * This ensures that filters on 'class_section_id' are session-aware and correctly catch promoted students.
     */
    public function scopeClassSection($query, $classSectionId)
    {
        $sessionYearId = app(CachingService::class)->getSessionYear()->id;
        return $query->where(function ($q) use ($classSectionId, $sessionYearId) {
            $q->where('students.class_section_id', $classSectionId)
                ->orWhereExists(function ($sub) use ($classSectionId, $sessionYearId) {
                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('promote_students')
                        ->whereColumn('promote_students.student_id', 'students.user_id')
                        ->where('promote_students.session_year_id', $sessionYearId)
                        ->where('promote_students.class_section_id', $classSectionId);
                });
        });
    }

    /**
     * Magic Accessor for class_section_id
     * Returns the historical session-aware ID if available, otherwise the current one.
     * This makes all existing queries 'just work' for both current and historical data.
     */
    public function getClassSectionIdAttribute($value)
    {
        if (isset($this->attributes['historical_class_section_id'])) {
            return $this->attributes['historical_class_section_id'];
        }
        return $value;
    }

    public function getRollNumberAttribute($value)
    {
        if (array_key_exists('historical_roll_number', $this->attributes) && $this->attributes['historical_roll_number'] !== null) {
            return $this->attributes['historical_roll_number'];
        }
        return $value;
    }

    public function announcement()
    {
        return $this->morphMany(Announcement::class, 'table');
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class)->withTrashed();
    }

    public function class()
    {
        return $this->belongsTo(ClassSchool::class)->withTrashed();
    }

    public function subjects()
    {
        $studentSubject = app(StudentSubjectInterface::class);
        //        $class_id = $this->class_section->class->id;
        $class_section_id = $this->class_section->id;
        $core_subjects = $this->class_section->class->core_subjects;
        $elective_subject_count = $this->class_section->class->elective_subject_groups->count();
        $elective_subjects = $studentSubject->builder()->where('student_id', $this->user_id)->where('class_section_id', $class_section_id)->select("subject_id")->with('subject')->get();
        $response = array(
            'core_subject' => $core_subjects
        );
        if ($elective_subject_count > 0) {
            $response['elective_subject'] = $elective_subjects;
        }
        return $response;
    }

    public function currentSemesterSubjects()
    {

        if (request('student_id')) {
            $student = $this->where('user_id', request('student_id'))->first();
        } else {
            $student = $this;
        }

        $studentSubject = app(StudentSubjectInterface::class);
        $cache = app(CachingService::class);
        $currentSemester = $cache->getDefaultSemesterData($student->school_id);
        $defaultSessionYear = $cache->getDefaultSessionYear();

        $class_section_id = $student->class_section->id;
        $core_subjects = $student->class_section->class->core_subjects()->where(function ($query) use ($currentSemester) {
            (isset($currentSemester) && !empty($currentSemester)) ? $query->where('semester_id', $currentSemester->id)->orWhereNull('semester_id') : $query->orWhereNull('semester_id');
        })->where('session_year_id', $defaultSessionYear->id)->get();

        $elective_subject_count = $student->class_section->class->elective_subject_groups()->where(function ($query) use ($currentSemester) {
            (isset($currentSemester) && !empty($currentSemester)) ? $query->where('semester_id', $currentSemester->id)->orWhereNull('semester_id') : $query->orWhereNull('semester_id');
        })->where('session_year_id', $defaultSessionYear->id)->count();

        $elective_subjects = $studentSubject->builder()->where('student_id', $student->user_id)
            ->where('class_section_id', $class_section_id)
            ->select("class_subject_id")
            ->whereHas('class_subject', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->with('class_subject.subject')
            ->where('session_year_id', $defaultSessionYear->id)
            ->get();

        $response = array(
            'core_subject' => $core_subjects
        );
        if ($elective_subject_count > 0) {
            $response['elective_subject'] = $elective_subjects;
        }

        return $response;
    }

    public function classSubjects()
    {
        $core_subjects = $this->class_section->class->core_subjects;
        $elective_subjects = $this->class_section->class->elective_subject_groups->load('subjects');
        return ['core_subject' => $core_subjects, 'elective_subject_group' => $elective_subjects];
    }

    public function currentSemesterClassSubjects()
    {
        $cache = app(CachingService::class);
        $defaultSessionYear = $cache->getDefaultSessionYear();
        $currentSemester = $cache->getDefaultSemesterData($this->school_id);
        $core_subjects = $this->class_section->class->core_subjects()->where(function ($query) use ($currentSemester) {
            (isset($currentSemester) && !empty($currentSemester)) ? $query->where('semester_id', $currentSemester->id)->orWhereNull('semester_id') : $query->orWhereNull('semester_id');
        })->where('session_year_id', $defaultSessionYear->id)->get();
        $elective_subjects = $this->class_section->class->elective_subject_groups()->where(function ($query) use ($currentSemester) {
            (isset($currentSemester) && !empty($currentSemester)) ? $query->where('semester_id', $currentSemester->id)->orWhereNull('semester_id') : $query->orWhereNull('semester_id');
        })->where('session_year_id', $defaultSessionYear->id)->with('subjects')->get();
        return ['core_subject' => $core_subjects, 'elective_subject_group' => $elective_subjects];
    }


    public function guardian()
    {
        return $this->belongsTo(User::class, 'guardian_id')->withTrashed();
    }

    //    public function scopeOfTeacher($query) {
    //        $user = Auth::user();
    //        if ($user->hasRole('Teacher')) {
    //            // for teacher list
    //            $class_teacher = $user->teacher->class_section;
    //            $class_section_id = array();
    //            if ($class_teacher) {
    //                $class_section_id[] = array($class_teacher->class_section_id);
    //            }
    //            $subject_teachers = $user->teacher->subjects;
    //            if ($subject_teachers) {
    //                foreach ($subject_teachers as $subject_teacher) {
    //                    $class_section_id[] = array($subject_teacher->class_section_id);
    //                }
    //            }
    //            return $query->whereIn('class_section_id', $class_section_id);
    //        }
    //
    //        // for admin list
    //        return $query;
    //        //return if it doesn't affect above conditions
    ////        return $query->where('class_section_id', 0);
    //    }

    public function fees_paid()
    {
        return $this->hasMany(FeesPaid::class, 'student_id')->withTrashed();
    }


    public function getFirstNameAttribute()
    {
        $firstName = '';
        if ($this->relationLoaded('user')) {
            $firstName .= $this->user->first_name;
        }
        return $firstName;
    }
    public function getLastNameAttribute()
    {
        $lastName = '';
        if ($this->relationLoaded('user')) {
            $lastName .= $this->user->last_name;
        }
        return $lastName;
    }
    public function getFullNameAttribute()
    {
        $fullName = '';
        if ($this->relationLoaded('user')) {
            $fullName .= $this->user->first_name . ' ' . $this->user->last_name;
        }
        return $fullName;
    }

    public function selectedStudentSubjects()
    {
        $studentSubject = app(StudentSubjectInterface::class);
        $cache = app(CachingService::class);
        $currentSemester = $cache->getDefaultSemesterData($this->school_id);

        $core_subjects = $this->class_section->class->core_subjects()->when($currentSemester, function ($query) use ($currentSemester) {
            return $query->where('semester_id', $currentSemester->id);
        })->orWhereNull('semester_id')->get();

        $subjects = $core_subjects->toArray();

        $elective_subject_count = $this->class_section->class->elective_subject_groups()->where(function ($query) use ($currentSemester) {
            (isset($currentSemester) && !empty($currentSemester)) ? $query->where('semester_id', $currentSemester->id)->orWhereNull('semester_id') : $query->orWhereNull('semester_id');
        })->count();

        if ($elective_subject_count > 0) {
            $elective_subjects = $studentSubject->builder()->where('student_id', $this->user_id)->with('class_subject.subject')->get();
            $subjects = array_merge($subjects, $elective_subjects->toArray());
        }
        return collect($subjects);
    }

    /**
     * Get all of the exam_result for the Students
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exam_result()
    {
        return $this->hasMany(ExamResult::class, 'student_id', 'user_id');
    }

    /**
     * Get all of the attendance for the Students
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'student_id', 'user_id');
    }

    public function session_year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    public function student_subjects()
    {
        return $this->hasMany(StudentSubject::class, 'student_id', 'user_id');
    }

    // public function elective_subject_groups() {
    //     return $this->hasMany(ElectiveSubjectGroup::class, 'class_id', 'class_id');
    // }

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }


    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }

    public function getAdmissionDateAttribute($value)
    {
        return $this->formatDateOnly($value);
    }

    public function promote_student()
    {
        return $this->hasOne(PromoteStudent::class, 'student_id', 'user_id');
    }


    public function promote_student_history()
    {
        return $this->hasOne(PromoteStudent::class, 'student_id', 'user_id');
    }
}
