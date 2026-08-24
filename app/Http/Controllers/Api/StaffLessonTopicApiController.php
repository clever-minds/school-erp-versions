<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LessonCommon;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\ClassSubject\ClassSubjectInterface;
use App\Repositories\Files\FilesInterface;
use App\Repositories\Lessons\LessonsInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\Subject\SubjectInterface;
use App\Repositories\SubjectTeacher\SubjectTeacherInterface;
use App\Repositories\Topics\TopicsInterface;
use App\Repositories\Semester\SemesterInterface;
use App\Repositories\StudentSubject\StudentSubjectInterface;
use App\Rules\DynamicMimes;
use App\Rules\MaxFileSize;
use App\Rules\uniqueTopicInLesson;
use App\Rules\YouTubeUrl;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use App\Repositories\SessionYear\SessionYearInterface;

class StaffLessonTopicApiController extends Controller
{
    private LessonsInterface $lesson;
    private TopicsInterface $topic;
    private FilesInterface $files;
    private ClassSectionInterface $classSection;
    private SubjectTeacherInterface $subjectTeacher;
    private StudentInterface $student;
    private SubjectInterface $subject;
    private CachingService $cache;
    private ClassSubjectInterface $class_subjects;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;
    private SemesterInterface $semester;
    private SessionYearInterface $sessionYear;
    private StudentSubjectInterface $studentSubject;

    public function __construct(
        LessonsInterface $lesson,
        TopicsInterface $topic,
        FilesInterface $files,
        ClassSectionInterface $classSection,
        SubjectTeacherInterface $subjectTeacher,
        StudentInterface $student,
        SubjectInterface $subject,
        CachingService $cache,
        ClassSubjectInterface $class_subjects,
        SessionYearsTrackingsService $sessionYearsTrackingsService,
        SemesterInterface $semester,
        SessionYearInterface $sessionYear,
        StudentSubjectInterface $studentSubject
    ) {
        $this->lesson = $lesson;
        $this->topic = $topic;
        $this->files = $files;
        $this->classSection = $classSection;
        $this->subjectTeacher = $subjectTeacher;
        $this->cache = $cache;
        $this->student = $student;
        $this->subject = $subject;
        $this->class_subjects = $class_subjects;
        $this->semester = $semester;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
        $this->sessionYear = $sessionYear;
        $this->studentSubject = $studentSubject;
    }

    public function index()
    {
        $request = request();
        ResponseService::noPermissionThenSendJson('topic-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $lesson_id = request('lesson_id');
        
        try {
            $sql = $this->topic->builder()->with('file', 'lesson')
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%")
                            ->orWhere('description', 'LIKE', "%$search%")
                            ->orWhere('created_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                            ->orWhere('updated_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                            ->orWhereHas('lesson', function ($q) use ($search) {
                                $q->where('name', 'LIKE', "%$search%");
                            });
                    });
                })
                ->when($lesson_id, function ($q) use ($lesson_id) {
                    $q->where('lesson_id', $lesson_id);
                });

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $rows = [];
            foreach ($res as $row) {
                $tempRow = $row->toArray();
                $tempRow['lesson_name'] = $row->lesson->name ?? '';
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];
            
            return ResponseService::successResponse('Lesson Topics fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffLessonTopicApiController -> index");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('topic-create');
        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit');

        $validator = Validator::make($request->all(), [
            'class_section_id'      => 'required|array',
            'class_section_id.*'    => 'numeric',
            'subject_id'            => 'required|numeric',
            'lesson_id'             => 'required|numeric',
            'name'                  => ['required', new uniqueTopicInLesson($request->lesson_id)],
            'description'           => 'required',
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

            $lessonTopicFileData = [];
            if (!empty($request->file_data)) {
                foreach ($request->file_data as $file) {
                    if ($file['type']) {
                        $lessonTopicFileData[] = $this->prepareFileData($file);
                    }
                }
            }

            $lessonTopicData = [
                'lesson_id' => $request->lesson_id,
                'name' => $request->name,
                'description' => $request->description,
                'school_id' => Auth::user()->school_id,
            ];
            
            $topics = $this->topic->create($lessonTopicData);

            if (!empty($lessonTopicFileData)) {
                $lessonFile = $this->files->model();
                $lessonModelAssociate = $lessonFile->modal()->associate($topics);
                foreach ($lessonTopicFileData as &$fileData) {
                    $fileData['modal_type'] = $lessonModelAssociate->modal_type;
                    $fileData['modal_id'] = $lessonModelAssociate->modal_id;
                }
                $this->files->createBulk($lessonTopicFileData);
            }

            $sessionYear = $this->cache->getDefaultSessionYear();
            $semester = $this->cache->getDefaultSemesterData();
            
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Topic', $topics->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, $semester ? $semester->id : null);
            
            // Push notification logic
            $section_ids = is_array($request->class_section_id) ? $request->class_section_id : [$request->class_section_id];
            $user = $this->student->builder()->with('user')->whereIn('class_section_id', $section_ids)->pluck('user_id')->toArray();
            $subjectName = $this->subject->builder()->where('id', $request->subject_id)->first();
            $lessonName = $this->lesson->builder()->where('id', $request->lesson_id)->first();
            
            DB::commit();
            send_notification($user, 'Topic Alert !!!', 'New Topic added in ' . ($lessonName->name ?? '') . ' for ' . ($subjectName->name ?? ''), 'topic');
            
            return ResponseService::successResponse('Lesson Topic Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                return ResponseService::warningResponse("Data Stored successfully. But App push notification not send.");
            }
            ResponseService::logErrorResponse($e, "StaffLessonTopicApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('topic-edit');

        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit');

        $validator = Validator::make($request->all(), [
            'class_section_id'      => 'required|array',
            'class_section_id.*'    => 'numeric',
            'subject_id'            => 'required|numeric',
            'lesson_id'             => 'required|numeric',
            'name'                  => ['required', new uniqueTopicInLesson($request->lesson_id, $id)],
            'description'           => 'required',
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
            $topic = $this->topic->findById($id);

            $lessonTopicData = [
                'lesson_id' => $request->lesson_id,
                'name' => $request->name,
                'description' => $request->description,
            ];
            $this->topic->update($id, $lessonTopicData);

            if (!empty($request->file_data)) {
                $lessonTopicFileData = [];
                $lessonFile = $this->files->model();
                $lessonModelAssociate = $lessonFile->modal()->associate($topic);
                
                foreach ($request->file_data as $file) {
                    if ($file['type']) {
                        $tempFileData = $this->prepareFileData($file);
                        $tempFileData['modal_type'] = $lessonModelAssociate->modal_type;
                        $tempFileData['modal_id'] = $lessonModelAssociate->modal_id;
                        $lessonTopicFileData[] = $tempFileData;
                    }
                }
                $this->files->createBulk($lessonTopicFileData);
            }

            DB::commit();
            return ResponseService::successResponse('Lesson Topic Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffLessonTopicApiController -> update");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('topic-delete');
        try {
            DB::beginTransaction();
            $this->topic->deleteById($id);
            
            $sessionYear = $this->cache->getDefaultSessionYear();
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Topic', $id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
            
            DB::commit();
            return ResponseService::successResponse('Lesson Topic Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffLessonTopicApiController -> destroy");
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
