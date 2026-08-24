<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LessonCommon;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\ClassSubject\ClassSubjectInterface;
use App\Repositories\Files\FilesInterface;
use App\Repositories\Lessons\LessonsInterface;
use App\Repositories\Semester\SemesterInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\Subject\SubjectInterface;
use App\Repositories\SubjectTeacher\SubjectTeacherInterface;
use App\Rules\DynamicMimes;
use App\Rules\MaxFileSize;
use App\Rules\YouTubeUrl;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class StaffLessonApiController extends Controller
{
    private SubjectTeacherInterface $subjectTeacher;
    private ClassSectionInterface $classSection;
    private LessonsInterface $lesson;
    private FilesInterface $files;
    private CachingService $cache;
    private StudentInterface $student;
    private SubjectInterface $subject;
    private ClassSubjectInterface $class_subjects;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

    public function __construct(
        ClassSectionInterface $classSection,
        LessonsInterface $lesson,
        FilesInterface $files,
        SubjectTeacherInterface $subjectTeacher,
        CachingService $cache,
        StudentInterface $student,
        SubjectInterface $subject,
        ClassSubjectInterface $class_subjects,
        SessionYearsTrackingsService $sessionYearsTrackingsService
    ) {
        $this->subjectTeacher = $subjectTeacher;
        $this->classSection = $classSection;
        $this->lesson = $lesson;
        $this->files = $files;
        $this->cache = $cache;
        $this->student = $student;
        $this->subject = $subject;
        $this->class_subjects = $class_subjects;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
    }

    public function index()
    {
        $request = request();
        ResponseService::noPermissionThenSendJson('lesson-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $class_section_id = request('class_section_id');
        $subject_id = request('subject_id');
        
        $sessionYear = $this->cache->getDefaultSessionYear();

        try {
            $sql = $this->lesson->builder()->with(['file', 'lesson_commons' => function ($q) {
                $q->with('class_section.class.medium', 'class_subject.subject');
            }, 'session_years_trackings'])
                ->whereHas('session_years_trackings', function ($q) use ($sessionYear) {
                    $q->where('session_year_id', $sessionYear->id);
                })
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%")
                            ->orWhere('description', 'LIKE', "%$search%")
                            ->orWhere('created_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                            ->orWhere('updated_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%");
                    });
                })->when($class_section_id, function ($q) use ($class_section_id) {
                    $q->whereHas('lesson_commons', function ($q) use ($class_section_id) {
                        $q->where('class_section_id', $class_section_id);
                    });
                })->when($subject_id, function ($q) use ($subject_id) {
                    $q->whereHas('lesson_commons', function ($q) use ($subject_id) {
                        $q->whereHas('class_subject', function ($q) use ($subject_id) {
                            $q->where('subject_id', $subject_id);
                        });
                    });
                });

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $rows = [];
            foreach ($res as $row) {
                $lessonCommons = $row->lesson_commons->map(function ($common) {
                    return $common->class_section ? $common->class_section->full_name : null;
                })->filter()->toArray();

                $tempRow = $row->toArray();
                $tempRow['class_section_name'] = implode(", ", $lessonCommons);
                $tempRow['subject_name'] = $row->lesson_commons->first()->class_subject->subject_with_name ?? '';
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];
            
            return ResponseService::successResponse('Lessons fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffLessonApiController -> index");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('lesson-create');

        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit');

        $validator = Validator::make($request->all(), [
            'name'                  => 'required',
            'description'           => 'required',
            'class_section_id'      => 'required|array',
            'class_section_id.*'    => 'numeric',
            'subject_id'            => 'required|numeric',
            'file_data'             => 'nullable|array',
            'file_data.*.type'      => 'required|in:file_upload,youtube_link,video_upload,other_link',
            'file_data.*.name'      => 'required_with:file_data.*.type',
            'file_data.*.thumbnail' => 'required_if:file_data.*.type,youtube_link,video_upload,other_link',
            'file_data.*.link'      => ['nullable', 'required_if:file_data.*.type,youtube_link', new YouTubeUrl],
            'file_data.*.link'      => ['nullable', 'required_if:file_data.*.type,other_link', 'url'],
            'file_data.*.file'      => ['nullable', 'required_if:file_data.*.type,file_upload,video_upload', new DynamicMimes(), new MaxFileSize($file_upload_size_limit)],
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $section_ids = is_array($request->class_section_id) ? $request->class_section_id : [$request->class_section_id];
            $lessonFileData = [];

            if (!empty($request->file_data)) {
                foreach ($request->file_data as $file) {
                    if ($file['type']) {
                        $lessonFileData[] = $this->prepareFileData($file);
                    }
                }
            }

            $lessonData = [
                'name' => $request->name,
                'description' => $request->description,
            ];
            
            $lesson = $this->lesson->create($lessonData);
            
            $lessonCommonData = [];
            foreach ($section_ids as $section_id) {
                if (Auth::user()->hasRole('Teacher')) {
                    $subjectTeacher = $this->subjectTeacher->builder()->where('class_section_id', $section_id)->where('subject_id', $request->subject_id)->first();
                    $class_subject_id = $subjectTeacher->class_subject_id ?? null;
                } else {
                    $classSection = $this->classSection->builder()->where('id', $section_id)->with('class_subject')->first();
                    $classSubject = $this->class_subjects->builder()->currentSemesterData()->where('class_id', $classSection->class_id)->where('subject_id', $request->subject_id)->first();
                    $class_subject_id = $classSubject->id ?? null;
                }

                $lessonCommonData[] = [
                    'lesson_id' => $lesson->id,
                    'class_section_id' => $section_id,
                    'class_subject_id' => $class_subject_id,
                ];
            }
            LessonCommon::insert($lessonCommonData);

            if (!empty($lessonFileData)) {
                $lessonFile = $this->files->model();
                $lessonModelAssociate = $lessonFile->modal()->associate($lesson);
                foreach ($lessonFileData as &$fileData) {
                    $fileData['modal_type'] = $lessonModelAssociate->modal_type;
                    $fileData['modal_id'] = $lessonModelAssociate->modal_id;
                }
                $this->files->createBulk($lessonFileData);
            }

            $sessionYear = $this->cache->getDefaultSessionYear();
            $semester = $this->cache->getDefaultSemesterData();
            
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Lesson', $lesson->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, $semester ? $semester->id : null);
            
            // Push Notification logic
            $user = $this->student->builder()->with('user')->whereIn('class_section_id', $section_ids)->pluck('user_id')->toArray();
            $subjectName = $this->subject->builder()->where('id', $request->subject_id)->first();
            
            DB::commit();
            send_notification($user, 'Lesson Alert !!!', 'New Lesson added for ' . ($subjectName->name ?? ''), 'lesson');
            
            return ResponseService::successResponse('Lesson Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                return ResponseService::warningResponse("Data Stored successfully. But App push notification not send.");
            }
            ResponseService::logErrorResponse($e, "StaffLessonApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('lesson-edit');

        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit');

        $validator = Validator::make($request->all(), [
            'name'                  => 'required',
            'description'           => 'required',
            'class_section_id'      => 'required|array',
            'class_section_id.*'    => 'numeric',
            'subject_id'            => 'required|numeric',
            'file_data'             => 'nullable|array',
            'file_data.*.type'      => 'required|in:file_upload,youtube_link,video_upload,other_link',
            'file_data.*.name'      => 'required_with:file_data.*.type',
            'file_data.*.thumbnail' => 'required_if:file_data.*.type,youtube_link,video_upload,other_link',
            'file_data.*.link'      => ['nullable', 'required_if:file_data.*.type,youtube_link', new YouTubeUrl],
            'file_data.*.link'      => ['nullable', 'required_if:file_data.*.type,other_link', 'url'],
            'file_data.*.file'      => ['nullable', 'required_if:file_data.*.type,file_upload,video_upload', new DynamicMimes(), new MaxFileSize($file_upload_size_limit)],
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $lesson = $this->lesson->findById($id);

            $lessonData = [
                'name' => $request->name,
                'description' => $request->description,
            ];
            $this->lesson->update($id, $lessonData);

            $section_ids = is_array($request->class_section_id) ? $request->class_section_id : [$request->class_section_id];
            
            LessonCommon::where('lesson_id', $id)->delete();
            $lessonCommonData = [];
            foreach ($section_ids as $section_id) {
                if (Auth::user()->hasRole('Teacher')) {
                    $subjectTeacher = $this->subjectTeacher->builder()->where('class_section_id', $section_id)->where('subject_id', $request->subject_id)->first();
                    $class_subject_id = $subjectTeacher->class_subject_id ?? null;
                } else {
                    $classSection = $this->classSection->builder()->where('id', $section_id)->with('class_subject')->first();
                    $classSubject = $this->class_subjects->builder()->currentSemesterData()->where('class_id', $classSection->class_id)->where('subject_id', $request->subject_id)->first();
                    $class_subject_id = $classSubject->id ?? null;
                }

                $lessonCommonData[] = [
                    'lesson_id' => $id,
                    'class_section_id' => $section_id,
                    'class_subject_id' => $class_subject_id,
                ];
            }
            LessonCommon::insert($lessonCommonData);

            if (!empty($request->file_data)) {
                $lessonFileData = [];
                $lessonFile = $this->files->model();
                $lessonModelAssociate = $lessonFile->modal()->associate($lesson);
                
                foreach ($request->file_data as $file) {
                    if ($file['type']) {
                        $tempFileData = $this->prepareFileData($file);
                        $tempFileData['modal_type'] = $lessonModelAssociate->modal_type;
                        $tempFileData['modal_id'] = $lessonModelAssociate->modal_id;
                        $lessonFileData[] = $tempFileData;
                    }
                }
                $this->files->createBulk($lessonFileData);
            }

            DB::commit();
            return ResponseService::successResponse('Lesson Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffLessonApiController -> update");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('lesson-delete');
        try {
            DB::beginTransaction();
            $this->lesson->deleteById($id);
            LessonCommon::where('lesson_id', $id)->delete();
            
            $sessionYear = $this->cache->getDefaultSessionYear();
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Lesson', $id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
            
            DB::commit();
            return ResponseService::successResponse('Lesson Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffLessonApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }

    private function prepareFileData($file)
    {
        if ($file['type']) {
            $tempFileData = [
                'file_name'  => $file['name']
            ];
            if ($file['type'] == "file_upload") {
                $tempFileData['type'] = 1;
                $tempFileData['file_thumbnail'] = null;
                $tempFileData['file_url'] = $file['file'];
            } elseif ($file['type'] == "youtube_link") {
                $tempFileData['type'] = 2;
                $tempFileData['file_thumbnail'] = $file['thumbnail'];
                $tempFileData['file_url'] = $file['link'];
            } elseif ($file['type'] == "video_upload") {
                $tempFileData['type'] = 3;
                $tempFileData['file_thumbnail'] = $file['thumbnail'];
                $tempFileData['file_url'] = $file['file'];
            } elseif ($file['type'] == "other_link") {
                $tempFileData['type'] = 4;
                $tempFileData['file_thumbnail'] = $file['thumbnail'];
                $tempFileData['file_url'] = $file['link'];
            }
            return $tempFileData;
        }
        return [];
    }
}
