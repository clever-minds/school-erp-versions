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
use App\Services\SessionYearsTrackingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use App\Jobs\BulkNotificationsJob;
use App\Repositories\SessionYear\SessionYearInterface;
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
    private SessionYearsTrackingsService $sessionYearsTrackingsService;
    private SemesterInterface $semester;
    private SessionYearInterface $sessionYear;
    private StudentSubjectInterface $studentSubject;
    public function __construct(LessonsInterface $lesson, TopicsInterface $topic, FilesInterface $files, ClassSectionInterface $classSection, SubjectTeacherInterface $subjectTeacher, StudentInterface $student, SubjectInterface $subject, CachingService $cache, ClassSubjectInterface $class_subjects, SessionYearsTrackingsService $sessionYearsTrackingsService, SemesterInterface $semester, SessionYearInterface $sessionYear, StudentSubjectInterface $studentSubject)
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
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
        $this->sessionYear = $sessionYear;
        $this->studentSubject = $studentSubject;
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
                $q->whereHas('class_subject', function ($q) {
                    $q->whereNull('deleted_at');
                });
            },
            'lesson_commons.class_subject' => function ($q) {
                $q->whereNull('deleted_at');
            },
            'session_years_trackings'
        ])->whereHas('session_years_trackings', function ($q) use ($sessionYear) {
            $q->where('session_year_id', $sessionYear->id);
        })->get();
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
            'file_data.*.link' => ['nullable', 'required_if:file_data.*.type,youtube_link,other_link', new YouTubeUrl],
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

            /* ================= SESSION TRACKING ================= */

            $sessionYear = $this->cache->getDefaultSessionYear();
            $semester = $this->cache->getDefaultSemesterData();

            $this->sessionYearsTrackingsService->storeSessionYearsTracking(
                'App\Models\LessonTopic',
                $topics->id,
                Auth::id(),
                $sessionYear->id,
                Auth::user()->school_id,
                $semester?->id
            );

            /* ================= BULK NOTIFICATIONS (FIXED) ================= */

            /**
             * SOURCE OF TRUTH → lesson_common
             */
            $lessonCommons = LessonCommon::query()
                ->where('lesson_id', $request->lesson_id)
                ->get(['class_section_id', 'class_subject_id']);

            // Resolve class_subject models
            $classSubjects = $this->class_subjects
                ->builder()
                ->with('subject')
                ->whereIn('id', $lessonCommons->pluck('class_subject_id'))
                ->get()
                ->keyBy('id');

            // section_id => class_subject
            $sectionMap = $lessonCommons->mapWithKeys(function ($lc) use ($classSubjects) {
                return [$lc->class_section_id => $classSubjects[$lc->class_subject_id]];
            });

            $title = 'Topic Alert !!!';
            $type = 'lesson';

            $studentsQuery = Students::query()->with('user')
                ->whereIn('class_section_id', $sectionMap->keys()->unique());

            // Split core / elective
            $coreSectionIds = $sectionMap
                ->filter(fn($cs) => $cs->type === 'Compulsory')
                ->keys()
                ->toArray();

            $electivePairs = $sectionMap
                ->filter(fn($cs) => $cs->type === 'Elective')
                ->map(fn($cs, $sectionId) => [
                    'class_section_id' => $sectionId,
                    'class_subject_id' => $cs->id,
                ])
                ->values();

            if ($electivePairs->isNotEmpty()) {
                $studentsQuery->where(function ($q) use ($coreSectionIds, $electivePairs) {

                    if (!empty($coreSectionIds)) {
                        $q->whereIn('class_section_id', $coreSectionIds);
                    }

                    $q->orWhereIn('user_id', function ($sub) use ($electivePairs) {
                        $sub->select('student_id')
                            ->from('student_subjects')
                            ->where(function ($inner) use ($electivePairs) {
                                foreach ($electivePairs as $pair) {
                                    $inner->orWhere(function ($c) use ($pair) {
                                        $c->where('class_section_id', $pair['class_section_id'])
                                            ->where('class_subject_id', $pair['class_subject_id']);
                                    });
                                }
                            });
                    });
                });
            }

            $students = $studentsQuery->get([
                'id',
                'user_id',
                'guardian_id',
                'class_section_id'
            ]);

            $userIds = [];
            $studentMap = [];   // user_id => student_id
            $guardianMap = [];   // guardian_user_id => [student_id]

            foreach ($students as $student) {
                $classSubject = $sectionMap[$student->class_section_id];
                $body = 'A new topic "' . $request->name .
                    '" has been added to the lesson under the subject "' . $classSubject->subject_with_name . '".';

                if (!empty($student->user_id)) {
                    $userIds[] = $student->user_id;
                    $studentMap[$student->user_id] = $student->id;
                }

                if (!empty($student->guardian_id)) {
                    $userIds[] = $student->guardian_id;
                    $guardianMap[$student->guardian_id][] = $student->id;
                }
            }

            /**
             * IMPORTANT:
             * section_map MUST be:
             *   class_section_id => class_subject_id
             * NOT models
             */
            $sectionMapForJob = $sectionMap
                ->map(fn($classSubject) => $classSubject->id)
                ->toArray();

            $userIds = array_values(array_unique($userIds));

            if (!empty($userIds)) {

                BulkNotificationsJob::dispatch(
                    auth()->user()->school_id,
                    $userIds,
                    'Topic Alert !!!',
                    $body,        // base body
                    'topic',
                    [
                        // 🔒 INTERNAL — USED BY JOB LOGIC
                        'internal' => [
                            'section_map' => $sectionMapForJob,
                            'student_map' => $studentMap,
                            'guardian_map' => $guardianMap,
                        ],

                        // 📦 PAYLOAD — JOB DOES NOT INTERPRET THIS
                        'payload' => [
                            'topic_id' => (string) $topics->id,
                            'lesson_id' => (string) $request->lesson_id,
                            'files' => $file_type ?? '',
                        ],
                    ]
                );
            }

            DB::commit();

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
        $semester_id = request('semester_id');
        $lesson_id = request('lesson_id');

        $sql = $this->topic->builder()
            ->has('lesson')
            ->with(
                [
                    'file',
                    'lesson',
                    'lesson.file',
                    'lesson.lesson_commons' => function ($q) {
                        $q->whereHas('class_subject', function ($q) {
                            $q->whereNull('deleted_at');
                        });
                    },
                    'lesson.lesson_commons.class_subject' => function ($q) {
                        $q->whereNull('deleted_at');
                    },
                    'lesson.lesson_commons.class_subject.subject',
                    'lesson.lesson_commons.class_section.class',
                    'lesson.lesson_commons.class_section.class.shift',
                    'lesson.lesson_commons.class_section.section',
                    'session_years_trackings'
                ]
            )
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")
                            ->orWhere('name', 'LIKE', "%$search%")
                            ->orWhere('description', 'LIKE', "%$search%")
                            ->orWhereHas('lesson.lesson_commons.class_section.section', function ($q) use ($search) {
                                $q->where('name', 'LIKE', "%$search%");
                            })
                            ->orWhereHas('lesson.lesson_commons.class_section.class', function ($q) use ($search) {
                                $q->where('name', 'LIKE', "%$search%");
                            })
                            ->orWhereHas('lesson.lesson_commons.class_subject.subject', function ($q) use ($search) {
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
                $query->whereHas('lesson.lesson_commons.class_subject', function ($q) use ($class_subject_id) {
                    $q->where('class_subjects.subject_id', $class_subject_id);
                });
            })
            ->when(request('class_section_id') != null, function ($query) {
                $class_section_id = request('class_section_id');
                $query->whereHas('lesson.lesson_commons.class_section', function ($q) use ($class_section_id) {
                    $q->where('class_section_id', $class_section_id);
                });
            })
            ->when(request('lesson_id') != null, function ($query) {
                $lesson_id = request('lesson_id');
                $query->whereHas('lesson', function ($q) use ($lesson_id) {
                    $q->where('id', $lesson_id);
                });
            })
            ->when(request('semester_id') != null, function ($query) {
                $semester_id = request('semester_id');
                $query->whereHas('session_years_trackings', function ($q) use ($semester_id) {
                    $q->where('semester_id', $semester_id);
                });
            });

        if (request('session_year_id')) {
            $sessionYear = request('session_year_id');
            $sql = $sql->whereHas('session_years_trackings', function ($q) use ($sessionYear) {
                $q->where('session_year_id', $sessionYear);
            });
        }

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
            foreach ($row->lesson->lesson_commons as $lessonCommon) {
                $classSections .= '<li>' . $lessonCommon->class_section->full_name . '</li> ';
            }

            // $operate = BootstrapTableService::button(route('lesson-topic.edit', $row->id), ['btn-gradient-primary'], ['title' => 'Edit'], ['fa fa-edit']);
            $operate = BootstrapTableService::button('fa fa-edit', route('lesson-topic.edit', $row->id), ['btn-gradient-primary'], ['title' => 'Edit']);
            $operate .= BootstrapTableService::deleteButton(route('lesson-topic.destroy', $row->id));

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            // $tempRow['class_section_with_medium'] = $lessonTopicCommons;
            $tempRow['class_section_with_medium'] = '<ol>' . $classSections . '</ol>';
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
        $lessons = $this->lesson->builder()->with('lesson_commons.class_subject')->get();


        $topic = $this->topic->builder()->with('file', 'lesson.lesson_commons.class_subject.subject', 'lesson.lesson_commons.class_section.class')->where('id', $id)->first();

        //dd($class_section->toArray());
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
            'file_data.*.link' => ['nullable', 'required_if:file_data.*.type,youtube_link', new YouTubeUrl],
            'file_data.*.file' => ['nullable', new DynamicMimes, new MaxFileSize($file_upload_size_limit)],
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            /* ---------------- UPDATE TOPIC (UNCHANGED) ---------------- */

            $data = $request->all();
            $data['class_section_id'] = $request->class_section_id[0];
            $topic = $this->topic->update($id, $data);

            /* ---------------- FILE UPDATE (UNCHANGED) ---------------- */

            if ($request->file_data) {
                foreach ($request->file_data as $file) {
                    if (!$file['type'])
                        continue;

                    $topicFile = $this->files->model();
                    $assoc = $topicFile->modal()->associate($topic);

                    $fileData = [
                        'id' => $file['id'] ?? null,
                        'modal_type' => $assoc->modal_type,
                        'modal_id' => $assoc->modal_id,
                        'file_name' => $file['name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    switch ($file['type']) {
                        case 'file_upload':
                            $fileData += ['type' => 1, 'file_url' => $file['file'] ?? null];
                            break;
                        case 'youtube_link':
                            $fileData += ['type' => 2, 'file_url' => $file['link'], 'file_thumbnail' => $file['thumbnail'] ?? null];
                            break;
                        case 'video_upload':
                            $fileData += ['type' => 3, 'file_url' => $file['file'] ?? null, 'file_thumbnail' => $file['thumbnail'] ?? null];
                            break;
                        case 'other_link':
                            $fileData += ['type' => 4, 'file_url' => $file['link'], 'file_thumbnail' => $file['thumbnail'] ?? null];
                            break;
                    }

                    $this->files->updateOrCreate(['id' => $file['id']], $fileData);
                    $file_type = $file['type'];
                    if ($file_type == "youtube_link") {
                        $file_type = "other_link";
                    }
                }
            }

            /* ================= FIXED NOTIFICATIONS ================= */

            /**
             * SOURCE OF TRUTH
             */
            $lessonCommons = LessonCommon::query()
                ->where('lesson_id', $request->lesson_id)
                ->get(['class_section_id', 'class_subject_id']);

            $classSubjects = $this->class_subjects
                ->builder()
                ->with('subject')
                ->whereIn('id', $lessonCommons->pluck('class_subject_id'))
                ->get()
                ->keyBy('id');

            // section_id => class_subject
            $sectionMap = $lessonCommons->mapWithKeys(
                fn($lc) =>
                [$lc->class_section_id => $classSubjects[$lc->class_subject_id]]
            );

            $lessonName = $this->lesson->builder()
                ->where('id', $request->lesson_id)
                ->value('name');

            $title = 'Topic Alert !!!';
            $type = 'lesson';

            $studentsQuery = Students::query()
                ->with('user')
                ->whereIn('class_section_id', $sectionMap->keys()->unique());

            // Core / Elective split
            $coreSectionIds = $sectionMap
                ->filter(fn($cs) => $cs->type === 'Compulsory')
                ->keys()
                ->toArray();

            $electivePairs = $sectionMap
                ->filter(fn($cs) => $cs->type === 'Elective')
                ->map(fn($cs, $sectionId) => [
                    'class_section_id' => $sectionId,
                    'class_subject_id' => $cs->id,
                ])
                ->values();

            if ($electivePairs->isNotEmpty()) {
                $studentsQuery->where(function ($q) use ($coreSectionIds, $electivePairs) {

                    if ($coreSectionIds) {
                        $q->whereIn('class_section_id', $coreSectionIds);
                    }

                    $q->orWhereIn('user_id', function ($sub) use ($electivePairs) {
                        $sub->select('student_id')
                            ->from('student_subjects')
                            ->where(function ($inner) use ($electivePairs) {
                                foreach ($electivePairs as $pair) {
                                    $inner->orWhere(function ($c) use ($pair) {
                                        $c->where('class_section_id', $pair['class_section_id'])
                                            ->where('class_subject_id', $pair['class_subject_id']);
                                    });
                                }
                            });
                    });
                });
            }

            $students = $studentsQuery->get(['id', 'user_id', 'guardian_id', 'class_section_id']);

            $userIds = [];
            $studentMap = [];   // user_id => student_id
            $guardianMap = [];   // guardian_user_id => [student_id]

            foreach ($students as $student) {
                $classSubject = $sectionMap[$student->class_section_id];
                $body = 'A topic has been updated for the lesson "' . $lessonName .
                    '" under the subject "' . $classSubject->subject_with_name . '".';

                if (!empty($student->user_id)) {
                    $userIds[] = $student->user_id;
                    $studentMap[$student->user_id] = $student->id;
                }

                if (!empty($student->guardian_id)) {
                    $userIds[] = $student->guardian_id;
                    $guardianMap[$student->guardian_id][] = $student->id;
                }
            }

            /**
             * IMPORTANT:
             * section_map MUST be:
             *   class_section_id => class_subject_id
             * NOT models
             */
            $sectionMapForJob = $sectionMap
                ->map(fn($classSubject) => $classSubject->id)
                ->toArray();

            $userIds = array_values(array_unique($userIds));

            if (!empty($userIds)) {

                BulkNotificationsJob::dispatch(
                    auth()->user()->school_id,
                    $userIds,
                    'Topic Alert !!!',
                    $body,        // base body
                    'topic',
                    [
                        // 🔒 INTERNAL — USED BY JOB LOGIC
                        'internal' => [
                            'section_map' => $sectionMapForJob,
                            'student_map' => $studentMap,
                            'guardian_map' => $guardianMap,
                        ],

                        // 📦 PAYLOAD — JOB DOES NOT INTERPRET THIS
                        'payload' => [
                            'topic_id' => (string) $id,
                            'lesson_id' => (string) $request->lesson_id,
                            'files' => $file_type ?? '',
                        ],
                    ]
                );
            }

            DB::commit();

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
            $sessionYear = $this->cache->getDefaultSessionYear();
            $this->sessionYearsTrackingsService->deleteSessionYearsTracking('App\Models\LessonTopic', $id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
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
