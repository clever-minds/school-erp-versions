<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OnlineExamCommon;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\ClassSubject\ClassSubjectInterface;
use App\Repositories\OnlineExam\OnlineExamInterface;
use App\Repositories\OnlineExamCommon\OnlineExamCommonInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\SubjectTeacher\SubjectTeacherInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Carbon\Carbon;

class StaffOnlineExamApiController extends Controller
{
    private ClassSectionInterface $classSection;
    private SubjectTeacherInterface $subjectTeacher;
    private OnlineExamInterface $onlineExam;
    private CachingService $cache;
    private StudentInterface $student;
    private ClassSubjectInterface $classSubjects;
    private SessionYearInterface $sessionYear;
    private OnlineExamCommonInterface $onlineExamCommon;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

    public function __construct(
        ClassSectionInterface $classSection,
        SubjectTeacherInterface $subjectTeacher,
        OnlineExamInterface $onlineExam,
        CachingService $cache,
        StudentInterface $student,
        ClassSubjectInterface $classSubjects,
        SessionYearInterface $sessionYear,
        OnlineExamCommonInterface $onlineExamCommon,
        SessionYearsTrackingsService $sessionYearsTrackingsService
    ) {
        $this->classSection = $classSection;
        $this->subjectTeacher = $subjectTeacher;
        $this->onlineExam = $onlineExam;
        $this->cache = $cache;
        $this->student = $student;
        $this->classSubjects = $classSubjects;
        $this->sessionYear = $sessionYear;
        $this->onlineExamCommon = $onlineExamCommon;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
    }

    public function index()
    {
        $request = request();
        ResponseService::noPermissionThenSendJson('online-exam-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');
        $subject_id = request('subject_id');
        $session_year_id = request('session_year_id');

        try {
            $sql = $this->onlineExam->builder()->with([
                'class_subject.subject',
                'question_choice',
                'online_exam_commons' => function ($q) {
                    $q->with('class_section.class.medium', 'class_subject.subject')->with([
                        'class_section.students' => function ($q) {
                            $q->whereHas('user', function ($q) {
                                $q->where('status', 1);
                            });
                        }
                    ]);
                }
            ])
                ->withCount('student_attempt')
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('id', 'LIKE', "%$search%")
                                ->orWhere('title', 'LIKE', "%$search%")
                                ->orWhere('exam_key', 'LIKE', "%$search%")
                                ->orWhereHas('class_subject.subject', function ($query) use ($search) {
                                    $query->where('name', 'LIKE', "%$search%")->orWhere('type', 'LIKE', "%$search%");
                                });
                        });
                    });
                })
                ->when(request('class_section_id') != null, function ($query) {
                    $class_id = request('class_section_id');
                    $query->whereHas('online_exam_commons', function ($q) use ($class_id) {
                        $q->where('class_section_id', $class_id);
                    });
                })
                ->when(request('class_subject_id') != null, function ($query) {
                    $query->whereHas('online_exam_commons', function ($q) {
                        $q->where('class_subject_id', request('class_subject_id'));
                    });
                })
                ->when($subject_id != null, function ($q) use ($subject_id) {
                    $q->where('class_subject_id', $subject_id);
                })
                ->when($session_year_id, function ($q) use ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                });

            $total = $sql->count();
            $sql = $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $rows = [];
            foreach ($res as $row) {
                $onlineExamCommons = $row->online_exam_commons->map(function ($common) {
                    return $common->class_section ? $common->class_section->full_name : null;
                })->filter()->toArray();

                $totalStudents = $row->online_exam_commons->map(function ($item) {
                    return $item->class_section ? $item->class_section->students->count() : 0;
                })->sum();

                $start = Carbon::parse($row->getRawOriginal('start_date'));
                $end = Carbon::parse($row->getRawOriginal('end_date'));

                if ($start > now()) {
                    $status = 'Upcoming';
                } elseif ($end < now()) {
                    $status = 'Completed';
                } else {
                    $status = 'Ongoing';
                }

                $studentAttempted = $row->student_attempt_count;

                $tempRow = $row->toArray();
                $tempRow['class_section_with_medium'] = implode(", ", $onlineExamCommons);
                $tempRow['subject_name'] = $row->online_exam_commons->first()->class_subject->subject_with_name ?? '';
                $tempRow['title'] = htmlspecialchars_decode($row->title);
                $tempRow['start_date'] = Carbon::parse($row->getRawOriginal('start_date'))->format('Y-m-d H:i');
                $tempRow['start_date_db'] = $row->start_date;
                $tempRow['end_date'] = Carbon::parse($row->getRawOriginal('end_date'))->format('Y-m-d H:i');
                $tempRow['end_date_db'] = $row->end_date;
                $tempRow['total_questions'] = $row->question_choice->count();
                $tempRow['status'] = $status;
                $tempRow['participants'] = $studentAttempted . '/' . $totalStudents;
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];

            return ResponseService::successResponse('Online Exams fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffOnlineExamApiController -> index");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('online-exam-create');
        $section_ids = is_array($request->class_section_id) ? $request->class_section_id : [$request->class_section_id];
        $request->validate([
            'class_section_id' => 'required|array',
            'class_section_id.*' => 'numeric',
            'subject_id' => 'required',
            'title' => 'required',
            'exam_key' => 'required|unique:online_exams,exam_key,NULL,id,school_id,' . Auth::user()->school_id,
            'duration' => 'required|numeric|gte:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            DB::beginTransaction();
            $sessionYear = $this->cache->getDefaultSessionYear();
            $onlineExamList = $request->except('subject_id');

            // Find class subject id logic based on role
            if ($request->class_section_id) {
                foreach ($request->class_section_id as $section_id) {
                    if (Auth::user()->hasRole('Teacher')) {
                        $classSection = $this->subjectTeacher->builder()->where('class_section_id', $section_id)->where('subject_id', $request->subject_id)->first();
                        if ($classSection && $classSection->class_subject) {
                            $onlineExamList['class_subject_id'] = $classSection->class_subject->id;
                        }
                    } else {
                        $classSection = $this->classSection->builder()->where('id', $section_id)->with([
                            'class_subject' => function ($q) use ($request) {
                                $q->where('subject_id', $request->subject_id);
                            }
                        ])->first();
                        if ($classSection && $classSection->class_subject->isNotEmpty()) {
                            $onlineExamList['class_subject_id'] = $classSection->class_subject->first()->id;
                        }
                    }
                }
            }

            $onlineExamList['session_year_id'] = $sessionYear->id;

            $onlineExam = $this->onlineExam->create($onlineExamList);

            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\OnlineExam', $onlineExam->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);

            $onlineExamCommonData = ['online_exam_id' => $onlineExam->id];

            foreach ($section_ids as $section_id) {
                if (Auth::user()->hasRole('Teacher')) {
                    $subjectTeacher = $this->subjectTeacher->builder()->where('class_section_id', $section_id)->where('subject_id', $request->subject_id)->first();
                    $onlineExamCommonData['class_section_id'] = $section_id;
                    $onlineExamCommonData['class_subject_id'] = $subjectTeacher->class_subject_id ?? null;
                    $this->onlineExamCommon->create($onlineExamCommonData);
                } else {
                    $classSection = $this->classSection->builder()->where('id', $section_id)->with('class_subject')->first();
                    $classSubject = $this->classSubjects->builder()->currentSemesterData()->where('class_id', $classSection->class_id)->where('subject_id', $request->subject_id)->first();
                    $onlineExamCommonData['class_section_id'] = $section_id;
                    $onlineExamCommonData['class_subject_id'] = $classSubject->id ?? null;
                    $this->onlineExamCommon->create($onlineExamCommonData);
                }
            }
            DB::commit();
            return ResponseService::successResponse('Online Exam Stored Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "StaffOnlineExamApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('online-exam-edit');
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'exam_key' => 'required|numeric',
            'duration' => 'required|numeric|gte:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $this->onlineExam->update($id, [
                'title' => $request->title,
                'exam_key' => $request->exam_key,
                'duration' => $request->duration,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
            DB::commit();
            return ResponseService::successResponse("Online Exam Updated Successfully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "StaffOnlineExamApiController -> update");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('online-exam-delete');
        try {
            DB::beginTransaction();
            $this->onlineExam->deleteById($id);
            $sessionYear = $this->cache->getDefaultSessionYear();
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\OnlineExam', $id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
            DB::commit();
            return ResponseService::successResponse('Online Exam Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "StaffOnlineExamApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }
}
