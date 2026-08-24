<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Announcement\AnnouncementInterface;
use App\Repositories\AnnouncementClass\AnnouncementClassInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\ClassSubject\ClassSubjectInterface;
use App\Repositories\Files\FilesInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\StudentSubject\StudentSubjectInterface;
use App\Repositories\SubjectTeacher\SubjectTeacherInterface;
use App\Rules\MaxFileSize;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StaffAnnouncementApiController extends Controller
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

    public function __construct(
        AnnouncementInterface $announcement,
        ClassSectionInterface $classSection,
        SubjectTeacherInterface $subjectTeacher,
        StudentInterface $student,
        FilesInterface $files,
        StudentSubjectInterface $studentSubject,
        ClassSubjectInterface $classSubject,
        CachingService $cachingService,
        AnnouncementClassInterface $announcementClass,
        SessionYearsTrackingsService $sessionYearsTrackingsService,
        SessionYearInterface $sessionYear
    ) {
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
        $request = request();
        ResponseService::noPermissionThenSendJson('announcement-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        try {
            $sql = $this->announcement->builder()->with('file', 'announcement_class.class_section.class', 'announcement_class.class_section.section', 'announcement_class.class_section.medium', 'announcement_class.class_subject.subject', 'session_years_trackings')
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")
                            ->orwhere('title', 'LIKE', "%$search%")
                            ->orwhere('description', 'LIKE', "%$search%");
                    });
                })
                ->when(request('session_year_id') != null, function ($query) use ($request) {
                    $query->where('session_year_id', $request->session_year_id);
                });

            if (request('semester_id') != null) {
                $semester_id = request('semester_id');
                $sql = $sql->whereHas('session_years_trackings', function ($q) use ($semester_id) {
                    $q->where('semester_id', $semester_id);
                });
            }

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $rows = [];
            foreach ($res as $row) {
                $row = (object)$row;
                $announcementClass = $row->announcement_class->map(function ($common) {
                    return $common->class_section ? $common->class_section->full_name : null;
                });
                
                $class_sections_list = $announcementClass->filter()->values()->toArray();
                
                $tempRow = $row->toArray();
                $tempRow['class_section_with_medium'] = implode(', ', $class_sections_list);
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];
            
            return ResponseService::successResponse('Announcements fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffAnnouncementApiController -> index");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('announcement-create');
        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit') ?? 20;
        
        $validator = Validator::make($request->all(), [
            'title'            => 'required',
            'class_section_id' => 'required|array',
            'subject_id'       => Auth::user() && Auth::user()->hasRole('Teacher') ? 'required|exists:subjects,id' : 'nullable|exists:subjects,id',
            'file'             => 'nullable|array',
            'file.*'           => ['mimes:jpeg,png,jpg,gif,svg,webp,pdf,doc,docx,xml', new MaxFileSize($file_upload_size_limit)],
            'add_url'          => $request->has('add_url') && !empty($request->add_url) ? 'nullable' : 'nullable',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $sessionYear = $this->cache->getDefaultSessionYear(); 
            $section_ids = is_array($request->class_section_id) ? $request->class_section_id : [$request->class_section_id];
            
            $announcementData = array(
                'title'           => $request->title,
                'description'     => $request->description,
                'session_year_id' => $sessionYear->id,
                'school_id'       => Auth::user()->school_id,
            );
            
            $announcement = $this->announcement->create($announcementData);

            $semester = $this->cache->getDefaultSemesterData();
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Announcement', $announcement->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, $semester ? $semester->id : null);

            $notifyUser = [];
            $title = '';
            
            if (!empty($request->subject_id)) {
                $teacherId = Auth::user()->id;
                $subjectName = "";
                
                foreach ($section_ids as $section_id) {
                    $classSection = $this->classSection->builder()->where('id', $section_id)->with('class')->first();
                    $classSubjects = $this->classSubject->builder()->where('class_id', $classSection->class->id)->where('subject_id', $request->subject_id)->first();
                    
                    if ($classSubjects) {
                        $subjectTeacherData = $this->subjectTeacher->builder()->with('subject')->where('class_section_id', $section_id)->where(['teacher_id' => $teacherId, 'class_subject_id' => $classSubjects->id])->first(); 
                        if ($subjectTeacherData) {
                            $subjectName = $subjectTeacherData->subject_with_name;
                        }

                        $getClassSubjectType = $classSubjects->type;
                        if ($getClassSubjectType == 'Elective') {
                            $getStudentId = $this->studentSubject->builder()->select('student_id')->where('class_section_id', $section_id)->where('class_subject_id', $classSubjects->id)->get()->pluck('student_id');
                            $notifyUsers = $this->student->builder()->select('user_id')->whereIn('id', $getStudentId)->get()->pluck('user_id')->toArray();
                            $notifyUser = array_merge($notifyUser, $notifyUsers);
                        } else {
                            $notifyUsers = $this->student->builder()->select('user_id')->where('class_section_id', $section_id)->get()->pluck('user_id')->toArray();
                            $notifyUser = array_merge($notifyUser, $notifyUsers);
                        }
                    }
                }
                $title = 'New announcement in ' . $subjectName;
            } else {
                $notifyUser = $this->student->builder()->select('user_id')->whereIn('class_section_id', $section_ids)->get()->pluck('user_id')->toArray();
                $title = 'New announcement';
            }

            foreach ($section_ids as $section_id) {
                $classSection = $this->classSection->builder()->where('id', $section_id)->with('class')->first();
                $classSubjects = $this->classSubject->builder()->where('class_id', $classSection->class->id)->where('subject_id', $request->subject_id)->first();

                if (!empty($request->subject_id) && $classSubjects) {
                    $announcementClassData = [
                        'announcement_id'   => $announcement->id,
                        'class_section_id'  => $section_id,
                        'class_subject_id'  => $classSubjects->id
                    ];
                } else {
                    $announcementClassData = [
                        'announcement_id'   => $announcement->id,
                        'class_section_id'  => $section_id,
                    ];
                }
        
                $announcementClass = $this->announcementClass->create($announcementClassData);
                
                $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\AnnouncementClass', $announcementClass->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, $semester ? $semester->id : null);
            }

            // Handle File Upload
            if ($request->hasFile('file')) {
                $fileData = array();
                $fileInstance = $this->files->model();
                $announcementModelAssociate = $fileInstance->modal()->associate($announcement);
                
                foreach ($request->file as $file_upload) {
                    $tempFileData = array(
                        'modal_type' => $announcementModelAssociate->modal_type,
                        'modal_id'   => $announcementModelAssociate->modal_id,
                        'file_name'  => $file_upload->getClientOriginalName(),
                        'type'       => 1,
                        'file_url'   => $file_upload
                    );
                    $fileData[] = $tempFileData; 
                }
                $this->files->createBulk($fileData);
            }
            
            if ($request->add_url) {
                $urlData = array();
                $urls = is_array($request->add_url) ? $request->add_url : [$request->add_url];
            
                foreach ($urls as $url) {
                    $urlParts = parse_url($url);
                    $fileName = basename($urlParts['path'] ?? '/');
                    if (empty($fileName) || $fileName == '/') $fileName = $url;
                    
                    $fileInstance = $this->files->model();
                    $announcementModelAssociate = $fileInstance->modal()->associate($announcement);
            
                    $tempUrlData = array(
                        'modal_type' => $announcementModelAssociate->modal_type,
                        'modal_id'   => $announcementModelAssociate->modal_id,
                        'file_name'  => $fileName, 
                        'type'       => 4,
                        'file_url'   => $url,
                    );
                    $urlData[] = $tempUrlData;
                }
                $this->files->createBulk($urlData);
            }
            
            DB::commit();

            if (!empty($notifyUser)) {
                send_notification(array_unique($notifyUser), $title, $request->title, 'announcement');
            }
            
            return ResponseService::successResponse('Announcement created successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffAnnouncementApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function update($id, Request $request)
    {
        ResponseService::noPermissionThenSendJson('announcement-edit');
        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit') ?? 20;

        $validator = Validator::make($request->all(), [
            'title'            => 'required',
            'class_section_id' => 'required|array',
            'subject_id'       => Auth::user() && Auth::user()->hasRole('Teacher') ? 'required|exists:subjects,id' : 'nullable|exists:subjects,id',
            'file'             => 'nullable|array',
            'file.*'           => ['mimes:jpeg,png,jpg,gif,svg,webp,pdf,doc,docx,xml', new MaxFileSize($file_upload_size_limit)],
            'add_url'          => $request->has('add_url') && !empty($request->add_url) ? 'nullable' : 'nullable',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $announcementData = array(
                'title'       => $request->title,
                'description' => $request->description,
            );
            $announcement = $this->announcement->update($id, $announcementData);

            $this->announcementClass->builder()->where('announcement_id', $id)->delete();

            $section_ids = is_array($request->class_section_id) ? $request->class_section_id : [$request->class_section_id];
            $sessionYear = $this->cache->getDefaultSessionYear();
            $semester = $this->cache->getDefaultSemesterData();

            foreach ($section_ids as $section_id) {
                $classSection = $this->classSection->builder()->where('id', $section_id)->with('class')->first();
                $classSubjects = $this->classSubject->builder()->where('class_id', $classSection->class->id)->where('subject_id', $request->subject_id)->first();

                if (!empty($request->subject_id) && $classSubjects) {
                    $announcementClassData = [
                        'announcement_id'   => $announcement->id,
                        'class_section_id'  => $section_id,
                        'class_subject_id'  => $classSubjects->id
                    ];
                } else {
                    $announcementClassData = [
                        'announcement_id'   => $announcement->id,
                        'class_section_id'  => $section_id,
                    ];
                }
        
                $announcementClass = $this->announcementClass->create($announcementClassData);
                $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\AnnouncementClass', $announcementClass->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, $semester ? $semester->id : null);
            }

            if ($request->hasFile('file')) {
                $fileData = array();
                $fileInstance = $this->files->model();
                $announcementModelAssociate = $fileInstance->modal()->associate($announcement);
                
                foreach ($request->file as $file_upload) {
                    $tempFileData = array(
                        'modal_type' => $announcementModelAssociate->modal_type,
                        'modal_id'   => $announcementModelAssociate->modal_id,
                        'file_name'  => $file_upload->getClientOriginalName(),
                        'type'       => 1,
                        'file_url'   => $file_upload
                    );
                    $fileData[] = $tempFileData; 
                }
                $this->files->createBulk($fileData);
            }

            if ($request->add_url) {
                $urlData = array();
                $urls = is_array($request->add_url) ? $request->add_url : [$request->add_url];
            
                foreach ($urls as $url) {
                    $urlParts = parse_url($url);
                    $fileName = basename($urlParts['path'] ?? '/');
                    if (empty($fileName) || $fileName == '/') $fileName = $url;
                    
                    $fileInstance = $this->files->model();
                    $announcementModelAssociate = $fileInstance->modal()->associate($announcement);
            
                    $tempUrlData = array(
                        'modal_type' => $announcementModelAssociate->modal_type,
                        'modal_id'   => $announcementModelAssociate->modal_id,
                        'file_name'  => $fileName, 
                        'type'       => 4,
                        'file_url'   => $url,
                    );
                    $urlData[] = $tempUrlData;
                }
                $this->files->createBulk($urlData);
            }

            DB::commit();
            return ResponseService::successResponse('Announcement updated successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffAnnouncementApiController -> update");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('announcement-delete');
        try {
            DB::beginTransaction();
            $announcement = $this->announcement->findById($id);

            if ($announcement->file) {
                foreach ($announcement->file as $file) {
                    if ($file->type == 1 && Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }
                }
                $announcement->file()->delete();
            }

            $this->announcementClass->builder()->where('announcement_id', $id)->delete();
            $this->announcement->deleteById($id);

            DB::commit();
            return ResponseService::successResponse('Announcement deleted successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffAnnouncementApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }
}
