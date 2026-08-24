<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OnlineExamQuestionCommon;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\ClassSubject\ClassSubjectInterface;
use App\Repositories\OnlineExamQuestion\OnlineExamQuestionInterface;
use App\Repositories\OnlineExamQuestionOption\OnlineExamQuestionOptionInterface;
use App\Repositories\SubjectTeacher\SubjectTeacherInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StaffOnlineExamQuestionApiController extends Controller
{
    private SubjectTeacherInterface $subjectTeacher;
    private ClassSectionInterface $classSection;
    private OnlineExamQuestionInterface $onlineExamQuestion;
    private OnlineExamQuestionOptionInterface $onlineExamQuestionOption;
    private CachingService $cache;
    private ClassSubjectInterface $classSubjects;
    private OnlineExamQuestionCommon $onlineExamQuestionCommon;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

    public function __construct(
        SubjectTeacherInterface $subjectTeacher,
        ClassSectionInterface $classSection,
        OnlineExamQuestionInterface $onlineExamQuestion,
        OnlineExamQuestionOptionInterface $onlineExamQuestionOption,
        CachingService $cache,
        ClassSubjectInterface $classSubjects,
        OnlineExamQuestionCommon $onlineExamQuestionCommon,
        SessionYearsTrackingsService $sessionYearsTrackingsService
    ) {
        $this->subjectTeacher = $subjectTeacher;
        $this->classSection = $classSection;
        $this->onlineExamQuestion = $onlineExamQuestion;
        $this->onlineExamQuestionOption = $onlineExamQuestionOption;
        $this->cache = $cache;
        $this->classSubjects = $classSubjects;
        $this->onlineExamQuestionCommon = $onlineExamQuestionCommon;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
    }

    public function index()
    {
        $request = request();
        ResponseService::noPermissionThenSendJson('online-exam-questions-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');
        $class_id = request('class_id');
        $subject_id = request('subject_id');
        $class_section_id = request('class_section_id');

        try {
            $sql = $this->onlineExamQuestion->builder()->with('options')
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('id', 'LIKE', "%$search%")
                                ->orWhere('question_type', 'LIKE', "%$search%")
                                ->orWhere('question', 'LIKE', "%$search%");
                        });
                    });
                })->when(request('class_section_id') != null, function ($query) {
                    $class_id = request('class_section_id');
                    $query->whereHas('online_exam_question_commons', function ($q) use ($class_id) {
                        $q->where('class_section_id', $class_id);
                    });
                })->when(request('class_subject_id') != null, function ($query) {
                    $query->whereHas('online_exam_question_commons', function ($q) {
                        $q->where('class_subject_id', request('class_subject_id'));
                    });
                })->when(request('subject_id') != null, function ($query) {
                    $subject_id = request('subject_id');
                    $query->whereHas('class_subject', function ($q) use ($subject_id) {
                        $q->where('subject_id', $subject_id);
                    });
                })
                ->with(['online_exam_question_commons' => function ($q) {
                    $q->with('class_section.class.medium', 'class_subject.subject');
                }]);

            $total = $sql->count();
            $sql = $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $rows = [];
            foreach ($res as $row) {
                $onlineExamQuestionCommons = $row->online_exam_question_commons->map(function ($common) {
                    return $common->class_section ? $common->class_section->full_name : null;
                })->filter()->toArray();

                $tempRow = $row->toArray();
                $tempRow['class_section_with_medium'] = implode(", ", $onlineExamQuestionCommons);
                $tempRow['subject_name'] = $row->online_exam_question_commons->first()->class_subject->subject_with_name ?? '';
                $tempRow['question'] = htmlspecialchars_decode($row->question);
                
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];
            return ResponseService::successResponse('Online Exam Questions fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffOnlineExamQuestionApiController -> index");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('online-exam-questions-create');
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|array',
            'class_section_id.*' => 'numeric',
            'subject_id' => 'required',
            'question_type' => 'required',
            'question' => 'required',
            'option_data' => 'required_if:question_type,0|array',
            'answer' => 'required|array',
            'image' => 'mimes:jpeg,png,jpg,svg,svg+xml|max:2048',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $section_ids = is_array($request->class_section_id) ? $request->class_section_id : [$request->class_section_id];

            if (empty($section_ids)) {
                return ResponseService::errorResponse('Class section ID is required');
            }

            // Get the first section to set initial data
            $firstSection = $this->classSection->builder()->where('id', $section_ids[0])->with('class')->firstOrFail();
            $classSubjects = $this->classSubjects->builder()->where('class_id', $firstSection->class->id)->where('subject_id', $request->subject_id)->firstOrFail();

            $onlineExamQuestionData = array_merge($request->all(), [
                'class_section_id' => $section_ids[0],
                'class_subject_id' => $classSubjects->id,
                'last_edited_by' => Auth::user()->id
            ]);

            $onlineExamQuestion = $this->onlineExamQuestion->create($onlineExamQuestionData);
            
            // Create common records for all sections
            foreach ($section_ids as $section_id) {
                $classSection = $this->classSection->builder()->where('id', $section_id)->with('class')->firstOrFail();
                $sectionClassSubjects = $this->classSubjects->builder()->where('class_id', $classSection->class->id)->where('subject_id', $request->subject_id)->firstOrFail();

                $this->onlineExamQuestionCommon->create([
                    'online_exam_question_id' => $onlineExamQuestion->id,
                    'class_section_id' => $section_id,
                    'class_subject_id' => $sectionClassSubjects->id
                ]);
            }

            // Create options if equation type is 0 (MCQ)
            if ($request->question_type == 0 && isset($request->option_data)) {
                $onlineExamOptionData = [];
                foreach ($request->option_data as $key => $optionValue) {
                    $is_answer = 0;
                    if (isset($request->answer) && in_array($optionValue['number'], $request->answer)) {
                        $is_answer = 1;
                    }
                    $onlineExamOptionData[$key] = [
                        'question_id' => $onlineExamQuestion->id,
                        'option'      => htmlspecialchars($optionValue['option'], ENT_QUOTES | ENT_HTML5),
                        'is_answer'   => $is_answer
                    ];
                }
                $this->onlineExamQuestionOption->createBulk($onlineExamOptionData);
            }

            $sessionYear = $this->cache->getDefaultSessionYear();
            $semester = $this->cache->getDefaultSemesterData();
            
            $this->sessionYearsTrackingsService->storeSessionYearsTracking(
                'App\Models\OnlineExamQuestion',
                $onlineExamQuestion->id,
                Auth::user()->id,
                $sessionYear->id,
                Auth::user()->school_id,
                $semester ? $semester->id : null
            );

            DB::commit();
            return ResponseService::successResponse('Online Exam Question Created Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "StaffOnlineExamQuestionApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('online-exam-questions-edit');
        $validator = Validator::make($request->all(), [
            'edit_question_type' => 'required',
            'edit_question' => 'required',
            'edit_option_data' => 'required_if:edit_question_type,0|array',
            'edit_answer' => 'required|array',
            'image' => 'mimes:jpeg,png,jpg,svg,svg+xml|max:2048',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $onlineExamQuestionData = [
                'question_type' => $request->edit_question_type,
                'question' => $request->edit_question,
                'last_edited_by' => Auth::user()->id
            ];

            if ($request->hasFile('image')) {
                $onlineExamQuestionData['image'] = $request->file('image');
            }
            if ($request->has('note')) {
                $onlineExamQuestionData['note'] = $request->note;
            }

            $this->onlineExamQuestion->update($id, $onlineExamQuestionData);
            
            // Re-create options
            if ($request->edit_question_type == 0 && isset($request->edit_option_data)) {
                $this->onlineExamQuestionOption->builder()->where('question_id', $id)->delete();
                $onlineExamOptionData = [];
                foreach ($request->edit_option_data as $key => $optionValue) {
                    $is_answer = 0;
                    if (isset($request->edit_answer) && in_array($optionValue['number'], $request->edit_answer)) {
                        $is_answer = 1;
                    }
                    $onlineExamOptionData[] = [
                        'question_id' => $id,
                        'option'      => htmlspecialchars($optionValue['option'], ENT_QUOTES | ENT_HTML5),
                        'is_answer'   => $is_answer
                    ];
                }
                if (!empty($onlineExamOptionData)) {
                    $this->onlineExamQuestionOption->createBulk($onlineExamOptionData);
                }
            } else {
                $this->onlineExamQuestionOption->builder()->where('question_id', $id)->delete();
            }

            DB::commit();
            return ResponseService::successResponse("Online Exam Question Updated Successfully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "StaffOnlineExamQuestionApiController -> update");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('online-exam-questions-delete');
        try {
            DB::beginTransaction();
            $this->onlineExamQuestion->deleteById($id);
            $sessionYear = $this->cache->getDefaultSessionYear();
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\OnlineExamQuestion', $id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
            DB::commit();
            return ResponseService::successResponse('Online Exam Question Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "StaffOnlineExamQuestionApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }
}
