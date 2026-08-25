<?php

namespace App\Http\Controllers;

use App\Models\LessonCommon;
use App\Models\Students;
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
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use App\Jobs\BulkNotificationsJobv3_0_0;
use App\Models\ClassSubject;
use App\Models\LessonTopicClass;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Services\LessonNotificationService;

class LessonTopicController extends Controller
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
    private SemesterInterface $semester;
    private SessionYearInterface $sessionYear;
    private StudentSubjectInterface $studentSubject;
    private LessonNotificationService $lessonNotificationService;


    public function __construct(LessonsInterface $lesson, TopicsInterface $topic, FilesInterface $files, ClassSectionInterface $classSection, SubjectTeacherInterface $subjectTeacher, StudentInterface $student, SubjectInterface $subject, CachingService $cache, ClassSubjectInterface $class_subjects, SemesterInterface $semester, SessionYearInterface $sessionYear, StudentSubjectInterface $studentSubject, LessonNotificationService $lessonNotificationService)
    {
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
        $this->sessionYear = $sessionYear;
        $this->studentSubject = $studentSubject;
        $this->lessonNotificationService = $lessonNotificationService;
    }

    public function index()
    {
        ResponseService::noFeatureThenRedirect('Lesson Management');
        ResponseService::noPermissionThenRedirect('topic-list');
        $sessionYear = $this->cache->getDefaultSessionYear();
        $class_section = $this->classSection->builder()->with('class', 'class.stream', 'class.shift', 'section', 'medium')->get();
        $subjectTeachers = $this->subjectTeacher->builder()->with('subject:id,name,type')->get();
        $lessons = $this->lesson->builder()->with([
            'lesson_commons' => function ($q) {
                $q->whereHas('syllabus.class_subject', function ($q) {
                    $q->whereNull('deleted_at');
                })->with('syllabus');
            }
        ])->get();
        $semesters = $this->semester->builder()->get();

        $sessionYears = $this->sessionYear->builder()->get();
        return response(view('lessons.topic', compact('class_section', 'subjectTeachers', 'lessons', 'semesters', 'sessionYears')));
    }

    public function store(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Lesson Management');
        ResponseService::noPermissionThenRedirect('topic-create');

        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit');

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|array',
            'class_section_id.*' => 'numeric',
            'subject_id' => 'required|numeric',
            'lesson_id' => 'required|numeric',
            'name' => ['required', new uniqueTopicInLesson($request->lesson_id)],
            'description' => 'required',
            'file_data' => 'nullable|array',
            'file_data.*.type' => 'required|in:file_upload,youtube_link,video_upload,other_link',
            'file_data.*.name' => 'required_with:file_data.*.type',
            'file_data.*.thumbnail' => 'required_if:file_data.*.type,youtube_link,video_upload,other_link',
            'file_data.*.link' => [
                'nullable',
                'required_if:file_data.*.type,youtube_link,other_link',
                function ($attribute, $value, $fail) use ($request) {
                    $index = explode('.', $attribute)[1];
                    $type = $request->file_data[$index]['type'] ?? null;
                    if ($type == 'youtube_link') {
                        $youtubeRule = new YouTubeUrl;
                        if (!$youtubeRule->passes($attribute, $value)) {
                            $fail($youtubeRule->message());
                        }
                    } elseif ($type == 'other_link') {
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $fail(trans('The provided link must be a valid URL.'));
                        }
                    }
                }
            ],
            'file_data.*.file' => [
                'nullable',
                'required_if:file_data.*.type,file_upload,video_upload',
                new DynamicMimes(),
                new MaxFileSize($file_upload_size_limit),
            ],
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            /* ================= TOPIC CREATE ================= */

            $topics = $this->topic->create([
                'lesson_id' => $request->lesson_id,
                'name' => $request->name,
                'description' => $request->description,
                'school_id' => Auth::user()->school_id,
            ]);

            // Store lesson topic classes
            $lessonTopicClasses = [];
            foreach ($request->class_section_id ?? [] as $class_section_id) {
                $lessonTopicClasses[] = [
                    'lesson_topic_id' => $topics->id,
                    'class_section_id' => $class_section_id,
                    'school_id' => Auth::user()->school_id,
                ];
            }
            if (!empty($lessonTopicClasses)) {
                LessonTopicClass::upsert($lessonTopicClasses, ['lesson_topic_id', 'class_section_id'], ['school_id']);
            }

            $topics = $topics->load('lesson_topic_class', 'lesson.lesson_commons.syllabus');

            /* ================= FILES (UNCHANGED) ================= */

            if (!empty($request->file_data)) {
                $lessonTopicFileData = [];
                foreach ($request->file_data as $file) {
                    if (!empty($file['type'])) {
                        $lessonTopicFileData[] = $this->prepareFileData($file);
                    }
                    $file_type = $file['type'];
                    if ($file_type == "youtube_link") {
                        $file_type = "other_link";
                    }
                }

                if ($lessonTopicFileData) {
                    $lessonFile = $this->files->model();
                    $lessonModelAssociate = $lessonFile->modal()->associate($topics);

                    foreach ($lessonTopicFileData as &$fileData) {
                        $fileData['modal_type'] = $lessonModelAssociate->modal_type;
                        $fileData['modal_id'] = $topics->id;
                    }

                    $this->files->createBulk($lessonTopicFileData);
                }
            }

            DB::commit();

            /* ================== PUSH NOTIFICATIONS ================== */
            $sectionIds = $topics->lesson_topic_class->pluck('class_section_id')->toArray();
            $subject_id = $topics->lesson->lesson_commons->first()->syllabus->subject_id;
            $this->lessonNotificationService->send($topics->lesson, $sectionIds, (int)$subject_id, 'created');



            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {

            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                DB::commit();
                ResponseService::warningResponse("Data Stored successfully. But App push notification not send.");
            } else {
                DB::rollBack();
                ResponseService::logErrorResponse($e, "Lesson Topic Controller -> Store Method");
                ResponseService::errorResponse();
            }
        }
    }

    private function prepareFileData($file)
    {

        if ($file['type']) {
            $tempFileData = [
                'file_name' => $file['name']
            ];
            // If File Upload
            if ($file['type'] == "file_upload") {

                // Add Type And File Url to TempDataArray and make Thumbnail data null
                $tempFileData['type'] = 1;
                $tempFileData['file_thumbnail'] = null;
                $tempFileData['file_url'] = $file['file'];
            } elseif ($file['type'] == "youtube_link") {

                // Add Type , Thumbnail and Link to TempDataArray
                $tempFileData['type'] = 2;
                $tempFileData['file_thumbnail'] = $file['thumbnail'];
                $tempFileData['file_url'] = $file['link'];
            } elseif ($file['type'] == "video_upload") {

                // Add Type , File Thumbnail and File URL to TempDataArray
                $tempFileData['type'] = 3;
                $tempFileData['file_thumbnail'] = $file['thumbnail'];
                $tempFileData['file_url'] = $file['file'];
            } elseif ($file['type'] == "other_link") {

                // Add Type , File Thumbnail and File URL to TempDataArray
                $tempFileData['type'] = 4;
                $tempFileData['file_thumbnail'] = $file['thumbnail'];
                $tempFileData['file_url'] = $file['link'];
            }
        }

        return $tempFileData;
    }

    public function show()
    {
        ResponseService::noFeatureThenRedirect('Lesson Management');
        ResponseService::noPermissionThenRedirect('topic-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = $this->topic->builder()
            ->has('lesson')
            ->with(
                [
                    'file',
                    'lesson',
                    'lesson.file',
                    'lesson.lesson_commons' => function ($q) {
                        $q->whereHas('syllabus.class_subject', function ($q) {
                            $q->whereNull('deleted_at');
                        });
                    },
                    'lesson.lesson_commons.syllabus.class_subject' => function ($q) {
                        $q->whereNull('deleted_at');
                    },
                    'lesson.lesson_commons.syllabus.subject'
                ]
            )->with(['lesson_topic_class' => function ($q) {
                $q->with(['class_section' => function ($q) {
                    $q->whereNull('deleted_at')
                        ->with('class.stream', 'section', 'medium');
                }]);
            }])
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")
                            ->orWhere('name', 'LIKE', "%$search%")
                            ->orWhere('description', 'LIKE', "%$search%")
                            ->orWhereHas('lesson_topic_class.class_section.class', function ($q) use ($search) {
                                $q->where('name', 'LIKE', "%$search%");
                            })
                            ->orWhereHas('lesson.lesson_commons.syllabus.subject', function ($q) use ($search) {
                                $q->where('name', 'LIKE', "%$search%");
                            })
                            ->orWhereHas('lesson', function ($q) use ($search) {
                                $q->where('name', 'LIKE', "%$search%");
                            });
                    });
                });
            })
            ->when(request('class_subject_id') != null, function ($query) {
                $class_subject_id = request('class_subject_id');
                $query->whereHas('lesson.lesson_commons.syllabus.class_subject', function ($q) use ($class_subject_id) {
                    $q->where('class_subjects.subject_id', $class_subject_id);
                });
            })
            ->when(request('class_section_id') != null, function ($query) {
                $class_section_id = request('class_section_id');
                $query->whereHas('lesson_topic_class', function ($q) use ($class_section_id) {
                    $q->where('class_section_id', $class_section_id);
                });
            })
            ->when(request('lesson_id') != null, function ($query) {
                $lesson_id = request('lesson_id');
                $query->whereHas('lesson', function ($q) use ($lesson_id) {
                    $q->where('id', $lesson_id);
                });
            });

        $total = $sql->count();
        if ($offset >= $total && $total > 0) {
            $lastPage = floor(($total - 1) / $limit) * $limit; // calculate last page offset
            $offset = $lastPage;
        }
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {

            $row = (object) $row;

            $classSections = '';
            foreach ($row->lesson_topic_class as $lessonTopicClass) {
                // $classSections .= '<li>' . $lessonCommon->class_section->full_name . '</li> ';
                if ($lessonTopicClass->class_section) {
                    $classSections .= '<li>' . $lessonTopicClass->class_section->full_name . '</li> ';
                }
            }

            // Get Subject Name
            $subject = $row->lesson->lesson_commons->first()?->syllabus?->subject;
            $subjectName = $subject ? $subject->name . ' - ' . $subject->type : null;

            // $operate = BootstrapTableService::button(route('lesson-topic.edit', $row->id), ['btn-gradient-primary'], ['title' => 'Edit'], ['fa fa-edit']);
            $operate = BootstrapTableService::button('fa fa-edit', route('lesson-topic.edit', $row->id), ['btn-action-edit'], ['title' => 'Edit']);
            $operate .= BootstrapTableService::deleteButton(route('lesson-topic.destroy', $row->id));

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            // $tempRow['class_section_with_medium'] = $lessonTopicCommons;
            $tempRow['class_section_with_medium'] = '<ol>' . $classSections . '</ol>';
            $tempRow['subject_with_name'] = $subjectName;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function edit($id)
    {
        ResponseService::noFeatureThenRedirect('Lesson Management');
        ResponseService::noPermissionThenRedirect('topic-edit');
        $class_section = $this->classSection->builder()->with('class', 'class.stream', 'class.shift', 'section', 'medium')->get();
        $subjectTeachers = $this->subjectTeacher->builder()->with('subject:id,name,type')->get();
        $lessons = $this->lesson->builder()->with('lesson_commons.syllabus.class_subject.subject')->get();


        $topic = $this->topic->builder()->with('file', 'lesson.lesson_commons.syllabus.subject', 'lesson_topic_class.class_section')->where('id', $id)->first();

        return response(view('lessons.edit_topic', compact('class_section', 'subjectTeachers', 'lessons', 'topic')));
    }

    public function update($id, Request $request)
    {
        ResponseService::noFeatureThenRedirect('Lesson Management');
        ResponseService::noPermissionThenRedirect('topic-edit');

        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit');

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|array',
            'class_section_id*' => 'required|numeric',
            'class_subject_id' => 'required|numeric',
            'lesson_id' => 'required|numeric',
            'name' => ['required', new uniqueTopicInLesson($request->lesson_id, $id)],
            'description' => 'required',
            'file_data' => 'nullable|array',
            'file_data.*.type' => 'required|in:file_upload,youtube_link,video_upload,other_link',
            'file_data.*.name' => 'required_with:file_data.*.type',
            'file_data.*.link' => [
                'nullable',
                'required_if:file_data.*.type,youtube_link,other_link',
                function ($attribute, $value, $fail) use ($request) {
                    $index = explode('.', $attribute)[1];
                    $type = $request->file_data[$index]['type'] ?? null;
                    if ($type == 'youtube_link') {
                        $youtubeRule = new YouTubeUrl;
                        if (!$youtubeRule->passes($attribute, $value)) {
                            $fail($youtubeRule->message());
                        }
                    } elseif ($type == 'other_link') {
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $fail(trans('The provided link must be a valid URL.'));
                        }
                    }
                }
            ],
            'file_data.*.file' => ['nullable', new DynamicMimes, new MaxFileSize($file_upload_size_limit)],
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        };

        try {
            DB::beginTransaction();

            /* ---------------- UPDATE TOPIC (UNCHANGED) ---------------- */
            $data = $request->all();
            $topic = $this->topic->update($id, $data);

            // Update lesson topic classes
            LessonTopicClass::where('lesson_topic_id', $id)->delete();
            $lessonTopicClasses = [];
            foreach ($request->class_section_id ?? [] as $class_section_id) {
                $lessonTopicClasses[] = [
                    'lesson_topic_id' => $id,
                    'class_section_id' => $class_section_id,
                    'school_id' => Auth::user()->school_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($lessonTopicClasses)) {
                LessonTopicClass::insert($lessonTopicClasses);
            }

            $topics = $topic->load('lesson_topic_class', 'lesson.lesson_commons.syllabus');

            /* ---------------- FILE UPDATE (UNCHANGED) ---------------- */

            if ($request->file_data) {
                foreach ($request->file_data as $key => $file) {
                    if (empty($file['type'])) {
                        continue;
                    }

                    $topicFile = $this->files->model();
                    $assoc = $topicFile->modal()->associate($topic);

                    $tempFileData = [
                        'id' => $file['id'] ?? null,
                        'modal_type' => $assoc->modal_type,
                        'modal_id' => $assoc->modal_id,
                        'file_name' => $file['name'],
                        'updated_at' => now(),
                    ];

                    if (empty($file['id'])) {
                        $tempFileData['created_at'] = now();
                    }

                    switch ($file['type']) {
                        case 'file_upload':
                            $tempFileData['type'] = 1;
                            if ($request->hasFile("file_data.$key.file")) {
                                $tempFileData['file_url'] = $file['file'];
                            }
                            break;
                        case 'youtube_link':
                            $tempFileData['type'] = 2;
                            if (!empty($file['link'])) {
                                $tempFileData['file_url'] = $file['link'];
                            }
                            if ($request->hasFile("file_data.$key.thumbnail")) {
                                $tempFileData['file_thumbnail'] = $file['thumbnail'];
                            }
                            break;
                        case 'video_upload':
                            $tempFileData['type'] = 3;
                            if ($request->hasFile("file_data.$key.file")) {
                                $tempFileData['file_url'] = $file['file'];
                            }
                            if ($request->hasFile("file_data.$key.thumbnail")) {
                                $tempFileData['file_thumbnail'] = $file['thumbnail'];
                            }
                            break;
                        case 'other_link':
                            $tempFileData['type'] = 4;
                            if (!empty($file['link'])) {
                                $tempFileData['file_url'] = $file['link'];
                            }
                            if ($request->hasFile("file_data.$key.thumbnail")) {
                                $tempFileData['file_thumbnail'] = $file['thumbnail'];
                            }
                            break;
                    }

                    $this->files->updateOrCreate(['id' => $file['id'] ?? null], $tempFileData);
                }
            }

            DB::commit();

            /* ================= FIXED NOTIFICATIONS ================= */

            $sectionIds = $topics->lesson_topic_class->pluck('class_section_id')->toArray();
            $subject_id = $topics->lesson->lesson_commons->first()->syllabus->subject_id;
            $this->lessonNotificationService->send($topics->lesson, $sectionIds, (int)$subject_id, 'updated');

            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {

            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                DB::commit();
                ResponseService::warningResponse("Data Stored successfully. But App push notification not send.");
            } else {
                DB::rollBack();
                ResponseService::logErrorResponse($e, "Lesson Topic Controller -> Update Method");
                ResponseService::errorResponse();
            }
        }
    }


    public function destroy($id)
    {
        ResponseService::noFeatureThenRedirect('Lesson Management');
        ResponseService::noPermissionThenSendJson('topic-delete');
        try {
            DB::beginTransaction();
            $this->topic->deleteById($id);
            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Lesson Topic Controller -> Delete Method");
            ResponseService::errorResponse();
        }
    }

    public function restore(int $id)
    {
        ResponseService::noFeatureThenRedirect('Lesson Management');
        ResponseService::noPermissionThenSendJson('topic-delete');
        try {
            $this->topic->findOnlyTrashedById($id)->restore();
            ResponseService::successResponse("Data Restored Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function trash($id)
    {
        ResponseService::noFeatureThenRedirect('Lesson Management');
        ResponseService::noPermissionThenSendJson('topic-delete');
        try {
            $this->topic->findOnlyTrashedById($id)->forceDelete();
            ResponseService::successResponse("Data Deleted Permanently");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }
}
