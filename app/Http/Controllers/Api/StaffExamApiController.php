<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use App\Services\CachingService;
use App\Services\SessionYearsTrackingsService;
use App\Repositories\Exam\ExamInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Str;
use App\Repositories\ExamTimetable\ExamTimetableInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\ClassTeachers\ClassTeachersInterface;
use App\Repositories\Student\StudentInterface;
use Carbon\Carbon;

class StaffExamApiController extends Controller
{
    private ExamInterface $exam;
    private CachingService $cache;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;
    private ExamTimetableInterface $examTimetable;
    private ClassSectionInterface $classSection;
    private ClassTeachersInterface $classTeachers;
    private StudentInterface $student;

    public function __construct(
        ExamInterface $exam,
        CachingService $cache,
        SessionYearsTrackingsService $sessionYearsTrackingsService,
        ExamTimetableInterface $examTimetable,
        ClassSectionInterface $classSection,
        ClassTeachersInterface $classTeachers,
        StudentInterface $student
    ) {
        $this->exam = $exam;
        $this->cache = $cache;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
        $this->examTimetable = $examTimetable;
        $this->classSection = $classSection;
        $this->classTeachers = $classTeachers;
        $this->student = $student;
    }

    public function examList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('exam-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = $request->search;
        $medium_id = request('medium_id');

        try {
            $sql = $this->exam->builder()->with([
                'class.medium',
                'class.stream',
                'class.section',
                'timetable.class_subject',
            ])->where('school_id', Auth::user()->school_id);

            $sql->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', "%$search%")
                        ->orWhere('description', 'LIKE', "%$search%")
                        ->orWhereHas('session_year', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'LIKE', "%$search%");
                        });
                });
            })->when(request('session_year_id') != null, function ($query) {
                $query->where('session_year_id', request('session_year_id'));
            })->when($medium_id, function ($query) use ($medium_id) {
                $query->whereHas('class', function ($q) use ($medium_id) {
                    $q->where('medium_id', $medium_id);
                });
            });

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = [
                'total' => $total,
                'data' => $res->toArray()
            ];
            return ResponseService::successResponse('Exam list fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffExamApiController -> examList');
            return ResponseService::errorResponse();
        }
    }

    public function examStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('exam-create');
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'session_year_id' => 'required',
            'class_id' => 'required|array',
            'class_id.*' => 'exists:classes,id'
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $sessionYear = $this->cache->getDefaultSessionYear();
            
            foreach ($request->class_id as $classId) {
                $exam = $this->exam->create([
                    'name' => $request->name,
                    'session_year_id' => $request->session_year_id,
                    'description' => $request->description,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'school_id' => Auth::user()->school_id,
                    'publish' => $request->publish ?? 0,
                    'last_result_submission_date' => $request->last_result_submission_date,
                    'class_id' => $classId
                ]);

                if ($sessionYear) {
                    $this->sessionYearsTrackingsService->storeSessionYearsTracking(
                        'App\Models\Exam',
                        $exam->id,
                        Auth::user()->id,
                        $sessionYear->id,
                        Auth::user()->school_id,
                        null
                    );
                }
            }

            DB::commit();
            return ResponseService::successResponse('Exam Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffExamApiController -> examStore");
            return ResponseService::errorResponse();
        }
    }

    public function examUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('exam-edit');
        
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $exam = $this->exam->builder()->where('id', $id)->where('school_id', Auth::user()->school_id)->first();
            if (!$exam) {
                return ResponseService::errorResponse('Exam not found');
            }

            // Cannot edit if already published
            if ($exam->publish == 1) {
                return ResponseService::errorResponse('Published exam cannot be edited');
            }

            $this->exam->update($id, [
                'name' => $request->name,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'last_result_submission_date' => $request->last_result_submission_date
            ]);

            return ResponseService::successResponse('Exam updated successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffExamApiController -> examUpdate");
            return ResponseService::errorResponse();
        }
    }

    public function examDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('exam-delete');

        try {
            $exam = $this->exam->builder()->where('id', $id)->where('school_id', Auth::user()->school_id)->first();
            if (!$exam) {
                return ResponseService::errorResponse('Exam not found');
            }

            $this->exam->deleteById($id);
            return ResponseService::successResponse('Exam deleted successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffExamApiController -> examDestroy");
            return ResponseService::errorResponse();
        }
    }

    public function getExamTimetable($examId)
    {
        ResponseService::noPermissionThenSendJson('exam-timetable-list');
        try {
            $currentSemester = $this->cache->getDefaultSemesterData();
            $exam = $this->exam->builder()->where('id', $examId)->where('school_id', Auth::user()->school_id)
                ->with(['class.medium', 'class.all_subjects' => function($query) use($currentSemester){
                    (isset($currentSemester) && !empty($currentSemester)) ? $query->where('semester_id',$currentSemester->id)->orWhereNull('semester_id') : $query->orWhereNull('semester_id');
                }, 'timetable'])->first();
            
            if (!$exam) {
                return ResponseService::errorResponse('Exam not found');
            }

            return ResponseService::successResponse('Exam timetable fetched successfully', $exam);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffExamApiController -> getExamTimetable");
            return ResponseService::errorResponse();
        }
    }

    public function updateExamTimetable(Request $request, $examId)
    {
        ResponseService::noPermissionThenSendJson('exam-timetable-create');
        
        $validator = Validator::make($request->all(), [
            'timetable'                 => 'required|array',
            'timetable.*.class_subject_id' => 'required',
            'timetable.*.total_marks'   => 'required|numeric',
            'timetable.*.passing_marks' => 'required|lte:timetable.*.total_marks',
            'timetable.*.start_time'    => 'required',
            'timetable.*.end_time'      => 'required|after:timetable.*.start_time',
            'timetable.*.date'          => 'required|date',
            'last_result_submission_date' => 'required|date',
        ], [
            'timetable.*.passing_marks.lte' => trans('passing_marks_should_less_than_or_equal_to_total_marks'),
            'timetable.*.end_time.after'    => trans('end_time_should_be_greater_than_start_time'),
            'last_result_submission_date.after'   => trans('the_exam_result_marks_submission_date_should_be_greater_than_last_exam_timetable_date'),
        ]);

        $validator->after(function ($validator) use ($request) {
            $timetable = $request->timetable;
            $lastResultDate = $request->last_result_submission_date;
          
            if (!empty($timetable) && $lastResultDate) {
                $latestExamDate = collect($timetable)
                ->pluck('date')
                ->map(fn($date) => Carbon::parse($date))
                ->max()
                ->format('Y-m-d'); 

                if ($latestExamDate && $lastResultDate <= $latestExamDate) {
                    $validator->errors()->add(
                        'last_result_submission_date',
                        trans('the_exam_result_marks_submission_date_should_be_greater_than_last_exam_timetable_date')
                    );
                }
            }
        });

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $exam = $this->exam->builder()->where('id', $examId)->where('school_id', Auth::user()->school_id)->first();
            if (!$exam) {
                return ResponseService::errorResponse('Exam not found');
            }

            DB::beginTransaction();

            foreach ($request->timetable as $timetable) {
                $examTimetable = array(
                    'exam_id'           => $examId,
                    'class_subject_id'  => $timetable['class_subject_id'],
                    'total_marks'       => $timetable['total_marks'],
                    'passing_marks'     => $timetable['passing_marks'],
                    'start_time'        => $timetable['start_time'],
                    'end_time'          => $timetable['end_time'],
                    'date'              => Carbon::parse($timetable['date'])->format('Y-m-d'),
                    'session_year_id'   => $exam->session_year_id,
                );
                $this->examTimetable->updateOrCreate(['id' => $timetable['id'] ?? null], $examTimetable);
            }

            // Get Start Date & End Date From Exam Timetable
            $examTimetableQuery = $this->examTimetable->builder()->where('exam_id', $examId);
            $startDate = $examTimetableQuery->min('date');
            $endDate = $examTimetableQuery->max('date');
            $last_result_submission_date = Carbon::parse($request->last_result_submission_date)->format('Y-m-d');
           
            $this->exam->update($examId, [
                'start_date' => $startDate,
                'end_date' => $endDate, 
                'last_result_submission_date' => $last_result_submission_date
            ]);

            // Get class sections for notifications
            $classSectionIds = $this->classSection->builder()
                ->where('class_id', $exam->class_id)
                ->pluck('id');
           
            $classTeacherIds = $this->classTeachers->builder()
                ->whereIn('class_section_id', $classSectionIds)
                ->distinct()
                ->pluck('teacher_id')
                ->toArray();

            // Send notifications
            $title = "Exams Timetable Scheduled";
            $body = "Exam Timetable Scheduled Click here to see !!!";
            $type = "exam";

            $students = $this->student->builder()
                ->whereHas('class_section', function ($q) use ($classSectionIds) {
                    $q->whereIn('class_id', $classSectionIds);
                })
                ->get();

            $guardian_ids = $students->pluck('guardian_id')->toArray();
            $student_ids = $students->pluck('user_id')->toArray();
            $users = array_unique(array_merge($student_ids, $guardian_ids, $classTeacherIds));

            DB::commit();
            
            if (function_exists('send_notification')) {
                send_notification($users, $title, $body, $type);
            }

            return ResponseService::successResponse('Exam timetable saved successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffExamApiController -> updateExamTimetable");
            return ResponseService::errorResponse();
        }
    }
}
