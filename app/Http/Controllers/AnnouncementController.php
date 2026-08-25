<?php

namespace App\Http\Controllers;

use App\Repositories\Announcement\AnnouncementInterface;
use App\Repositories\AnnouncementClass\AnnouncementClassInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\ClassSubject\ClassSubjectInterface;
use App\Repositories\Files\FilesInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\StudentSubject\StudentSubjectInterface;
use App\Repositories\SubjectTeacher\SubjectTeacherInterface;
use App\Rules\MaxFileSize;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use App\Services\GeneralFunctionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use TypeError;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Jobs\BulkNotificationsJob;

class AnnouncementController extends Controller
{

    private AnnouncementInterface $announcement;
    private ClassSectionInterface $classSection;
    private SubjectTeacherInterface $subjectTeacher;
    private StudentInterface $student;
    private FilesInterface $files;
    private StudentSubjectInterface $studentSubject;
    private ClassSubjectInterface $classSubject;
    private CachingService $cache;
    private AnnouncementClassInterface $announcementClass;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;
    private SessionYearInterface $sessionYear;
    public function __construct(AnnouncementInterface $announcement, ClassSectionInterface $classSection, SubjectTeacherInterface $subjectTeacher, StudentInterface $student, FilesInterface $files, StudentSubjectInterface $studentSubject, ClassSubjectInterface $classSubject, CachingService $cachingService, AnnouncementClassInterface $announcementClass, SessionYearsTrackingsService $sessionYearsTrackingsService, SessionYearInterface $sessionYear)
    {
        $this->announcement = $announcement;
        $this->classSection = $classSection;
        $this->subjectTeacher = $subjectTeacher;
        $this->student = $student;
        $this->files = $files;
        $this->studentSubject = $studentSubject;
        $this->classSubject = $classSubject;
        $this->cache = $cachingService;
        $this->announcementClass = $announcementClass;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
        $this->sessionYear = $sessionYear;
    }


    public function index()
    {
        ResponseService::noFeatureThenRedirect('Announcement Management');
        ResponseService::noPermissionThenRedirect('announcement-list');

        if (Auth::user()->hasRole('Teacher')) {
            $teacherId = Auth::id() ?? null;
            $class_section = $this->classSection->builder()
                ->whereHas('subject_teachers', function ($query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId);
                })
                ->with('class', 'class.stream', 'class.shift', 'section', 'medium')
                ->get();
            // dd($class_section);
        } else {
            $class_section = $this->classSection->builder()->with('class', 'class.stream', 'class.shift', 'section', 'medium')->get(); // Get the Class Section of Teacher
        }
        $subjectTeachers = $this->subjectTeacher->builder()->with(['subject:id,name,type'])->get();
        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit');
        $sessionYears = $this->sessionYear->builder()->get();
        return view('announcement.index', compact('class_section', 'subjectTeachers', 'file_upload_size_limit', 'sessionYears'));
    }

    public function store(Request $request)
    {
        ResponseService::noFeatureThenSendJson('Announcement Management');
        ResponseService::noPermissionThenSendJson('announcement-create');

        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit');

        $request->validate([
            'title' => 'required',
            'class_section_id' => 'required|array',
            'subject_id' => Auth::user() && Auth::user()->hasRole('Teacher')
                ? 'required|exists:subjects,id'
                : 'nullable|exists:subjects,id',
            'file' => 'nullable|array',
            'file.*' => [
                'mimes:jpeg,png,jpg,gif,svg,webp,pdf,doc,docx,xml',
                new MaxFileSize($file_upload_size_limit)
            ],
            'add_url' => $request->checkbox_add_url ? 'required' : 'nullable',
        ], [
            'class_section_id.required' => trans('the_class_section_field_id_required'),
            'file.*' => trans(
                'The file Uploaded must be less than :file_upload_size_limit MB.',
                ['file_upload_size_limit' => $file_upload_size_limit]
            ),
        ]);

        try {
            DB::beginTransaction();

            /* ================= CORE LOGIC (UNCHANGED) ================= */

            $sessionYear = $this->cache->getDefaultSessionYear();
            $section_ids = is_array($request->class_section_id)
                ? $request->class_section_id
                : [$request->class_section_id];

            $announcement = $this->announcement->create([
                'title' => $request->title,
                'description' => $request->description,
                'session_year_id' => $sessionYear->id,
                'school_id' => Auth::user()->school_id,
            ]);

            $this->sessionYearsTrackingsService->storeSessionYearsTracking(
                'App\Models\Announcement',
                $announcement->id,
                Auth::user()->id,
                $sessionYear->id,
                Auth::user()->school_id,
                null
            );

            /* ================= ANNOUNCEMENT CLASS (UNCHANGED) ================= */

            foreach ($section_ids as $section_id) {

                $classSection = $this->classSection
                    ->builder()
                    ->where('id', $section_id)
                    ->with('class')
                    ->first();

                $classSubject = null;

                if (!empty($request->subject_id)) {
                    $classSubject = $this->classSubject
                        ->builder()
                        ->where('class_id', $classSection->class->id)
                        ->where('subject_id', $request->subject_id)
                        ->first();
                }

                $announcementClassData = [
                    'announcement_id' => $announcement->id,
                    'class_section_id' => $section_id,
                ];

                if ($classSubject) {
                    $announcementClassData['class_subject_id'] = $classSubject->id;
                }

                $announcementClass = $this->announcementClass->create($announcementClassData);

                $semester = $this->cache->getDefaultSemesterData();

                $this->sessionYearsTrackingsService->storeSessionYearsTracking(
                    'App\Models\AnnouncementClass',
                    $announcementClass->id,
                    Auth::user()->id,
                    $sessionYear->id,
                    Auth::user()->school_id,
                    $semester?->id
                );
            }

            /* ================= FILE / URL (UNCHANGED) ================= */

            if ($request->hasFile('file')) {
                $fileData = [];
                $assoc = $this->files->model()->modal()->associate($announcement);

                foreach ($request->file as $file_upload) {
                    $fileData[] = [
                        'modal_type' => $assoc->modal_type,
                        'modal_id' => $assoc->modal_id,
                        'file_name' => $file_upload->getClientOriginalName(),
                        'type' => 1,
                        'file_url' => $file_upload
                    ];
                }

                $this->files->createBulk($fileData);
            }

            if ($request->add_url) {
                $urlData = [];
                $urls = is_array($request->add_url) ? $request->add_url : [$request->add_url];

                foreach ($urls as $url) {
                    $assoc = $this->files->model()->modal()->associate($announcement);
                    $urlData[] = [
                        'modal_type' => $assoc->modal_type,
                        'modal_id' => $assoc->modal_id,
                        'file_name' => basename(parse_url($url, PHP_URL_PATH) ?? '/'),
                        'type' => 4,
                        'file_url' => $url,
                    ];
                }

                $this->files->createBulk($urlData);
            }

            DB::commit();

            /* ================= FIXED NOTIFICATIONS (CORE + ELECTIVE SAFE) ================= */

            $announcementClasses = $this->announcementClass
                ->builder()
                ->where('announcement_id', $announcement->id)
                ->get(['class_section_id', 'class_subject_id']);

            $sectionMap = [];
            foreach ($announcementClasses as $ac) {
                $sectionMap[$ac->class_section_id] = $ac->class_subject_id;
            }

            $studentsQuery = \App\Models\Students::query()->with('user')
                ->whereIn('class_section_id', array_keys($sectionMap));

            $coreSectionIds = [];
            $electivePairs = [];

            foreach ($sectionMap as $sectionId => $classSubjectId) {

                if (empty($classSubjectId)) {
                    // No subject → treat as core
                    $coreSectionIds[] = $sectionId;
                    continue;
                }

                $classSubject = $this->classSubject->findById($classSubjectId, ['type']);

                if ($classSubject->type === 'Elective') {
                    $electivePairs[] = [
                        'class_section_id' => $sectionId,
                        'class_subject_id' => $classSubjectId,
                    ];
                } else {
                    $coreSectionIds[] = $sectionId;
                }
            }

            $studentsQuery->where(function ($q) use ($coreSectionIds, $electivePairs) {

                if (!empty($coreSectionIds)) {
                    $q->whereIn('class_section_id', $coreSectionIds);
                }

                if (!empty($electivePairs)) {
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
                }
            });

            $students = $studentsQuery->get([
                'id',
                'user_id',
                'guardian_id',
                'class_section_id'
            ]);

            $subjectName = null;
            if (!empty($request->subject_id)) {
                $subjectName = \App\Models\Subject::query()->where('id', $request->subject_id)
                    ->first();
            }

            $title = $subjectName
                ? trans('New announcement in') . ' ' . $subjectName->name_with_type
                : trans('New announcement');

            $body = $request->title;
            $type = 'Class Section';

            $userIds = [];
            $studentMap = []; // user_id => student_id
            $guardianMap = []; // guardian_user_id => student_id

            foreach ($students as $student) {

                if ($student->user_id) {
                    $userIds[] = $student->user_id;
                    $studentMap[$student->user_id] = $student->id;
                }

                if ($student->guardian_id) {
                    $userIds[] = $student->guardian_id;
                    $guardianMap[$student->guardian_id][] = $student->id;
                }
            }

            $userIds = array_values(array_unique($userIds));

            if (!empty($userIds)) {
                BulkNotificationsJob::dispatch(
                    auth()->user()->school_id,
                    $userIds,
                    $title,
                    $body,
                    'Announcement',
                    [
                        // 🔒 INTERNAL (logic only)
                        'internal' => [
                            'section_map' => $sectionMap,
                            'student_map' => $studentMap,
                            'guardian_map' => $guardianMap,
                        ],

                        // 📦 PAYLOAD (FCM only)
                        'payload' => [
                            'subject_id' => $classSubject?->subject_id,
                        ],
                    ]
                );
            }

            ResponseService::successResponse('Data Stored Successfully');

        } catch (Throwable $e) {

            $notificationStatus = app(GeneralFunctionService::class)
                ->wrongNotificationSetup($e);

            if ($notificationStatus) {
                DB::rollBack();
                ResponseService::logErrorResponse(
                    $e,
                    "Announcement Controller -> Store Method"
                );
                ResponseService::errorResponse();
            } else {
                DB::commit();
                ResponseService::warningResponse(
                    "Data Stored successfully. But App push notification not send."
                );
            }
        }
    }

    public function update($id, Request $request)
    {
        ResponseService::noFeatureThenSendJson('Announcement Management');
        ResponseService::noPermissionThenSendJson('announcement-edit');

        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit');

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'class_section_id' => 'required',
            'file' => 'nullable|array',
            'file.*' => [
                'mimes:jpeg,png,jpg,gif,svg,webp,pdf,doc,docx,xml',
                new MaxFileSize($file_upload_size_limit)
            ]
        ], [
            'file.*' => trans('The file Uploaded must be less than :file_upload_size_limit MB.', [
                'file_upload_size_limit' => $file_upload_size_limit,
            ]),
        ]);

        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            /* ================= CORE UPDATE (UNCHANGED) ================= */

            $sessionYear = $this->cache->getDefaultSessionYear();

            $announcement = $this->announcement->update($id, [
                'title' => $request->title,
                'description' => $request->description,
                'session_year_id' => $sessionYear->id,
            ]);

            $oldClassSection = $this->announcement
                ->findById($id)
                ->announcement_class
                ->pluck('class_section_id')
                ->toArray();

            $announcementClassData = [];
            $customData = [];
            $subjectName = null;

            $sectionIds = is_array($request->class_section_id)
                ? $request->class_section_id
                : [$request->class_section_id];

            /* ================= SUBJECT / CLASS HANDLING (UNCHANGED) ================= */

            if (!empty($request->subject_id)) {

                $teacherId = Auth::user()->id;

                foreach ($sectionIds as $class_section) {

                    $classSection = $this->classSection
                        ->builder()
                        ->where('id', $class_section)
                        ->with('class')
                        ->first();

                    $classSubjects = $this->classSubject
                        ->builder()
                        ->where('class_id', $classSection->class->id)
                        ->where('subject_id', $request->subject_id)
                        ->first();

                    $subjectTeacherData = $this->subjectTeacher
                        ->builder()
                        ->whereIn('class_section_id', $sectionIds)
                        ->where([
                            'teacher_id' => $teacherId,
                            'class_subject_id' => $classSubjects->id
                        ])
                        ->first();

                    $subjectName = ($subjectTeacherData->subject->name ?? null) . ' - ' . ($subjectTeacherData->subject->type ?? null);

                    $customData = [
                        'class_subject_id' => $classSubjects->id ?? null,
                        'subject_id' => $classSubjects->subject_id ?? null,
                        'subject_name' => $subjectName ?? null,
                    ];

                    $announcementClassData[] = [
                        'announcement_id' => $announcement->id,
                        'class_section_id' => $class_section,
                        'class_subject_id' => $classSubjects->id
                    ];

                    if (($key = array_search($class_section, $oldClassSection)) !== false) {
                        unset($oldClassSection[$key]);
                    }
                }

                $title = trans('Updated announcement in') . $subjectName;

            } else {

                foreach ($sectionIds as $class_section) {

                    $announcementClassData[] = [
                        'announcement_id' => $announcement->id,
                        'class_section_id' => $class_section
                    ];

                    if (($key = array_search($class_section, $oldClassSection)) !== false) {
                        unset($oldClassSection[$key]);
                    }
                }

                $customData = [];
                $title = trans('Updated announcement');
            }

            $this->announcementClass->upsert(
                $announcementClassData,
                ['announcement_id', 'class_section_id', 'school_id'],
                ['announcement_id', 'class_section_id', 'school_id', 'class_subject_id']
            );

            $this->announcementClass
                ->builder()
                ->where('announcement_id', $id)
                ->whereIn('class_section_id', $oldClassSection)
                ->delete();

            /* ================= FILE / URL (UNCHANGED) ================= */

            if ($request->hasFile('file')) {
                $fileData = [];
                $assoc = $this->files->model()->modal()->associate($announcement);

                foreach ($request->file as $file_upload) {
                    $fileData[] = [
                        'modal_type' => $assoc->modal_type,
                        'modal_id' => $assoc->modal_id,
                        'file_name' => $file_upload->getClientOriginalName(),
                        'type' => 1,
                        'file_url' => $file_upload
                    ];
                }

                $this->files->createBulk($fileData);
            }

            if ($request->add_url) {
                $assoc = $this->files->model()->modal()->associate($announcement);

                $this->files->upsert([
                    [
                        'id' => $request->add_url_id ?? null,
                        'modal_type' => $assoc->modal_type,
                        'modal_id' => $assoc->modal_id,
                        'file_name' => '',
                        'type' => 4,
                        'file_url' => $request->add_url,
                    ]
                ], ['id'], ['id', 'modal_type', 'modal_id', 'file_name', 'type', 'file_url']);
            } else {
                if ($request->add_url_id) {
                    $this->files->deleteById($request->add_url_id);
                }
            }

            DB::commit();

            /* ================= FIXED NOTIFICATIONS (CORE + ELECTIVE SAFE) ================= */

            $announcementClasses = $this->announcementClass
                ->builder()
                ->where('announcement_id', $announcement->id)
                ->get(['class_section_id', 'class_subject_id']);

            $sectionMap = [];
            foreach ($announcementClasses as $ac) {
                $sectionMap[$ac->class_section_id] = $ac->class_subject_id;
            }

            $studentsQuery = \App\Models\Students::query()->with('user')
                ->whereIn('class_section_id', array_keys($sectionMap));

            $coreSections = [];
            $electivePairs = [];

            foreach ($sectionMap as $sectionId => $classSubjectId) {

                if (empty($classSubjectId)) {
                    $coreSections[] = $sectionId;
                    continue;
                }

                $classSubject = $this->classSubject->findById($classSubjectId, ['type']);

                if ($classSubject->type === 'Elective') {
                    $electivePairs[] = [
                        'class_section_id' => $sectionId,
                        'class_subject_id' => $classSubjectId,
                    ];
                } else {
                    $coreSections[] = $sectionId;
                }
            }

            $studentsQuery->where(function ($q) use ($coreSections, $electivePairs) {

                if (!empty($coreSections)) {
                    $q->whereIn('class_section_id', $coreSections);
                }

                if (!empty($electivePairs)) {
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
                }
            });

            $students = $studentsQuery->get([
                'id',
                'user_id',
                'guardian_id',
                'class_section_id'
            ]);

            $body = $request->title;
            $type = 'Class Section';

            $userIds = [];
            $studentMap = [];   // user_id => student_id
            $guardianMap = [];  // guardian_user_id => [student_id, student_id...]

            foreach ($students as $student) {

                if ($student->user_id) {
                    $userIds[] = $student->user_id;
                    $studentMap[$student->user_id] = $student->id;
                }

                if ($student->guardian_id) {
                    $userIds[] = $student->guardian_id;

                    if (!isset($guardianMap[$student->guardian_id])) {
                        $guardianMap[$student->guardian_id] = [];
                    }

                    $guardianMap[$student->guardian_id][] = $student->id;
                }
            }

            $userIds = array_values(array_unique($userIds));
            if (!empty($userIds)) {
                BulkNotificationsJob::dispatch(
                    Auth::user()->school_id,
                    $userIds,
                    $title,
                    $body,
                    'Announcement',
                    [
                        // 🔒 INTERNAL (logic only)
                        'internal' => [
                            'section_map' => $sectionMap,
                            'student_map' => $studentMap,
                            'guardian_map' => $guardianMap,
                        ],

                        // 📦 PAYLOAD (FCM only)
                        'payload' => [
                            'subject_id' => $classSubject?->subject_id,
                        ],
                    ]
                );
            }

            ResponseService::successResponse('Data Updated Successfully');

        } catch (Throwable $e) {

            $notificationStatus = app(GeneralFunctionService::class)
                ->wrongNotificationSetup($e);

            if ($notificationStatus) {
                DB::rollBack();
                ResponseService::logErrorResponse(
                    $e,
                    "Announcement Controller -> Update Method"
                );
                ResponseService::errorResponse();
            } else {
                DB::commit();
                ResponseService::warningResponse(
                    "Data Stored successfully. But App push notification not send."
                );
            }
        }
    }

    public function show()
    {
        ResponseService::noFeatureThenSendJson('Announcement Management');
        ResponseService::noPermissionThenSendJson('announcement-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');
        $class_section_id = request('class_section_id');
        $subject_id = request('subject_id');
        $session_year_id = request('session_year_id');
        $sql = $this->announcement->builder()->with('file', 'announcement_class.class_section.class', 'announcement_class.class_section.section', 'announcement_class.class_section.medium', 'announcement_class.class_subject.subject', 'session_years_trackings')
            ->where(function ($q) use ($search) {
                $q->when($search, function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orwhere('title', 'LIKE', "%$search%")
                        ->orwhere('description', 'LIKE', "%$search%");
                });
            });

        // Filter by class section if provided
        if ($class_section_id) {
            $sql->whereHas('announcement_class', function ($q) use ($class_section_id) {
                $q->where('class_section_id', $class_section_id);
            });
        }

        // Filter by subject if provided
        if ($subject_id) {
            $sql->whereHas('announcement_class.class_subject', function ($q) use ($subject_id) {
                $q->where('subject_id', $subject_id);
            });
        }

        if ($session_year_id) {
            $sql = $sql->whereHas('session_years_trackings', function ($q) use ($session_year_id) {
                $q->where('session_year_id', $session_year_id);
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
        $user = Auth::user();
        foreach ($res as $row) {
            $operate = '';
            $class_section = array();
            $class_section_id = array();
            $class_subject_id = '';
            // $class->roles->id == $user->id
            foreach ($row->announcement_class as $index => $class) {
                if ($user->hasRole('School Admin') || !$user->hasRole('Teacher')) {
                    $operate = BootstrapTableService::editButton(route('announcement.update', $row->id));
                    $operate .= BootstrapTableService::deleteButton(route('announcement.destroy', $row->id));
                }

                if (($user->hasRole('School Admin') && ($class->class_subject_id == "" || $class->class_subject_id)) || ($user->hasRole('Teacher') && $class->class_subject_id)) {
                    //Show Edit and Soft Delete Buttons
                    $operate = BootstrapTableService::editButton(route('announcement.update', $row->id));
                    $operate .= BootstrapTableService::deleteButton(route('announcement.destroy', $row->id));
                }
                $class_section_id[] = $class->class_section_id;

                // Add teacher subject
                if ($class->class_subject_id) {
                    $class_subject_id = $class->class_subject_id;
                    $class_section[] = $class->class_section->full_name . ' #' . $class->class_subject->subject->name;
                } else {
                    $class_section[] = $class->class_section->full_name;
                }
            }

            $tempRow = $row->toArray();
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['class_subject_id'] = $class_subject_id;
            $tempRow['class_sections'] = $class_section_id;
            $tempRow['assignto'] = $class_section;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function destroy($id)
    {
        ResponseService::noFeatureThenSendJson('Announcement Management');
        ResponseService::noPermissionThenSendJson('announcement-delete');
        try {
            DB::beginTransaction();
            $this->announcement->deleteById($id);
            $sessionYear = $this->cache->getDefaultSessionYear();
            $semester = $this->cache->getDefaultSemesterData();
            if ($semester) {
                $this->sessionYearsTrackingsService->deleteSessionYearsTracking('App\Models\Announcement', $id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, $semester->id);
            } else {
                $this->sessionYearsTrackingsService->deleteSessionYearsTracking('App\Models\Announcement', $id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
            }
            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Announcement Controller -> Destroy Method");
            ResponseService::errorResponse();
        }
    }

    public function fileDelete($id)
    {
        ResponseService::noFeatureThenSendJson('Announcement Management');
        ResponseService::noPermissionThenSendJson('announcement-delete');
        try {
            DB::beginTransaction();

            // Find the Data by FindByID
            $file = $this->files->findById($id);

            // Delete the file data
            $file->delete();

            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Announcement Controller -> fileDelete Method");
            ResponseService::errorResponse();
        }
    }
}
