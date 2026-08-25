<?php

namespace App\Http\Controllers\Exam;

use Throwable;
use Illuminate\Http\Request;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Repositories\Exam\ExamInterface;
use Illuminate\Support\Facades\Validator;
use App\Repositories\ExamTimetable\ExamTimetableInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\ClassTeachers\ClassTeachersInterface;
use App\Repositories\Student\StudentInterface;
use App\Models\ClassSubject;
use Carbon\Carbon;

class ExamTimetableController extends Controller
{
    private ExamInterface $exam;
    private ExamTimetableInterface $examTimetable;
    private CachingService $cache;
    private ClassSectionInterface $classSection;
    private ClassTeachersInterface $classTeachers;
    private StudentInterface $student;

    public function __construct(ExamInterface $exam, ExamTimetableInterface $examTimetable, CachingService $cache, ClassSectionInterface $classSection, ClassTeachersInterface $classTeachers, StudentInterface $student)
    {
        $this->exam = $exam;
        $this->examTimetable = $examTimetable;
        $this->cache = $cache;
        $this->classSection = $classSection;
        $this->classTeachers = $classTeachers;
        $this->student = $student;
    }

    public function edit($examId)
    {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenRedirect('exam-timetable-list');
        $currentSessionYear = $this->cache->getDefaultSessionYear();
        $currentSemester = $this->cache->getDefaultSemesterData();
        $exam = $this->exam->builder()->where(['id' => $examId])->with([
            'class.medium',
            'class.all_subjects' => function ($query) use ($currentSemester) {
                (isset($currentSemester) && !empty($currentSemester)) ? $query->where('semester_id', $currentSemester->id)->orWhereNull('semester_id') : $query->orWhereNull('semester_id');
            },
            'timetable'
        ])->firstOrFail();
        $last_result_submission_date = date('d-m-Y', strtotime($exam->getRawOriginal('last_result_submission_date')));
        $disabled = $exam->publish ? 'disabled' : '';
        $schoolSettings = $this->cache->getSchoolSettings();

        // dd($exam->toArray());
        return response(view('exams.timetable', compact('exam', 'currentSessionYear', 'disabled', 'last_result_submission_date', 'schoolSettings')));
    }

    public function update(Request $request, $examID)
    {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenSendJson('exam-timetable-create');
        $validator = Validator::make($request->all(), [
            'timetable' => 'required|array',
            'timetable.*.passing_marks' => 'required|lte:timetable.*.total_marks',
            'timetable.*.end_time' => 'required|after:timetable.*.start_time',
            'timetable.*.date' => 'required|date',
            'last_result_submission_date' => 'required|date',
        ], [
            'timetable.*.passing_marks.lte' => trans('passing_marks_should_less_than_or_equal_to_total_marks'),
            'timetable.*.end_time.after' => trans('end_time_should_be_greater_than_start_time'),
            'last_result_submission_date.after' => trans('the_exam_result_marks_submission_date_should_be_greater_than_last_exam_timetable_date'),
        ]);

        $validator->after(function ($validator) use ($request) {
            $timetable = $request->timetable;
            $lastResultDate = $request->last_result_submission_date;

            if (!empty($timetable) && $lastResultDate) {
                try {
                    // Extract the latest date from the timetable using flexible parsing
                    $latestExamDate = collect($timetable)
                        ->pluck('date')
                        ->filter()
                        ->map(fn($date) => Carbon::parse($date))
                        ->max()
                        ->format('Y-m-d');

                    $lastResultDate = Carbon::parse($lastResultDate)->format('Y-m-d');

                    if ($lastResultDate <= $latestExamDate) {
                        $validator->errors()->add(
                            'last_result_submission_date',
                            trans('the_exam_result_marks_submission_date_should_be_greater_than_last_exam_timetable_date')
                        );
                    }
                } catch (\Exception $e) {
                    // If date parsing fails, skip this validation
                    // The main date validation will catch invalid dates
                }
            }
        });

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $class_subject_ids = [];
            $electiveSubjectsIds = [];
            foreach ($request->timetable as $timetable) {
                $examTimetable = array(
                    'exam_id' => $examID,
                    'class_subject_id' => $timetable['class_subject_id'],
                    'total_marks' => $timetable['total_marks'],
                    'passing_marks' => $timetable['passing_marks'],
                    'start_time' => $timetable['start_time'],
                    'end_time' => $timetable['end_time'],
                    'date' => date('Y-m-d', strtotime($timetable['date'])),
                    'session_year_id' => $request->session_year_id,
                );
                $class_subject_ids[] = $timetable['class_subject_id'];
                $electiveSubjects = ClassSubject::where('id', $timetable['class_subject_id'])->where('type', 'Elective')->pluck('id')->toArray();
                $electiveSubjectsIds = array_merge($electiveSubjectsIds, $electiveSubjects);
                $this->examTimetable->updateOrCreate(['id' => $timetable['id'] ?? null], $examTimetable);
            }

            // Get Start Date & End Date From Exam Timetable
            $examTimetable = $this->examTimetable->builder()->where('exam_id', $examID);
            $startDate = $examTimetable->min('date');
            $endDate = $examTimetable->max('date');
            $last_result_submission_date = date('Y-m-d', strtotime($request->last_result_submission_date));

            // Update Start Date and End Date to the particular Exam
            $exam = $this->exam->update($examID, ['start_date' => $startDate, 'end_date' => $endDate, 'last_result_submission_date' => $last_result_submission_date]);
            // dd($exam);
            DB::commit();

            //Get class sections for notifications
            $classSectionIds = $this->classSection->builder()
                ->where('class_id', $exam->class_id)
                ->pluck('id');

            $classTeacherIds = $this->classTeachers->builder()
                ->whereIn('class_section_id', $classSectionIds)
                ->distinct()
                ->pluck('teacher_id')
                ->toArray();

            $class_subject_ids = array_unique($class_subject_ids);
            $electiveSubjectsIds = array_unique($electiveSubjectsIds);

            $allSubjectsAreElective =
                count($class_subject_ids) === count($electiveSubjectsIds) &&
                empty(array_diff($class_subject_ids, $electiveSubjectsIds));

            // Send notifications
            $title = "Exams Timetable Scheduled";
            $body = "Exam Timetable Scheduled Click here to see !!!";
            $type = "exam";
            $students = $this->student->builder()
                ->where('application_status', 1)
                ->whereIn('class_section_id', $classSectionIds)
                ->whereHas('user', function ($query) {
                    $query->where('status', 1)->withTrashed();
                })
                ->where('session_year_id', $request->session_year_id);

            if ($allSubjectsAreElective) {
                $students = $students->whereHas('student_subjects', function ($q) use ($electiveSubjectsIds) {
                    $q->whereIn('class_subject_id', $electiveSubjectsIds);
                });
            }

            $students = $students->get();

            $guardian_ids = $students->pluck('guardian_id')->toArray();
            $student_ids = $students->pluck('user_id')->toArray();
            $users = array_unique(array_merge($student_ids, $guardian_ids, $classTeacherIds));
            $customData = ['exam_id' => $examID, 'exam_name' => $exam->name, 'exam_type' => 'offline'];
            send_notification($users, $title, $body, $type, $customData);

            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Exam Timetable Controller -> Store method");
            ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenSendJson('exam-timetable-delete');
        try {
            $this->examTimetable->deleteById($id);
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Exam Controller -> DeleteTimetable method");
            ResponseService::errorResponse();
        }
    }
}
