<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Assignment\AssignmentInterface;
use App\Repositories\AssignmentCommon\AssignmentCommonInterface;
use App\Repositories\AssignmentSubmission\AssignmentSubmissionInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\ClassSubject\ClassSubjectInterface;
use App\Repositories\Files\FilesInterface;
use App\Repositories\Semester\SemesterInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\Subject\SubjectInterface;
use App\Repositories\SubjectTeacher\SubjectTeacherInterface;
use App\Rules\MaxFileSize;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class StaffAssignmentApiController extends Controller
{
    private AssignmentInterface $assignment;
    private ClassSectionInterface $classSection;
    private SubjectInterface $subject;
    private FilesInterface $files;
    private StudentInterface $student;
    private SessionYearInterface $sessionYear;
    private CachingService $cache;
    private SubjectTeacherInterface $subjectTeacher;
    private AssignmentCommonInterface $assignmentCommon;
    private ClassSubjectInterface $class_subjects;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;
    private SemesterInterface $semester;
    private AssignmentSubmissionInterface $assignmentSubmission;

    public function __construct(
        AssignmentInterface $assignment,
        ClassSectionInterface $classSection,
        SubjectInterface $subject,
        FilesInterface $files,
        StudentInterface $student,
        SessionYearInterface $sessionYear,
        CachingService $cache,
        SubjectTeacherInterface $subjectTeacher,
        AssignmentCommonInterface $assignmentCommon,
        ClassSubjectInterface $class_subjects,
        SessionYearsTrackingsService $sessionYearsTrackingsService,
        SemesterInterface $semester,
        AssignmentSubmissionInterface $assignmentSubmission
    ) {
        $this->assignment = $assignment;
        $this->classSection = $classSection;
        $this->subject = $subject;
        $this->files = $files;
        $this->student = $student;
        $this->sessionYear = $sessionYear;
        $this->cache = $cache;
        $this->subjectTeacher = $subjectTeacher;
        $this->assignmentCommon = $assignmentCommon;
        $this->class_subjects = $class_subjects;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
        $this->semester = $semester;
        $this->assignmentSubmission = $assignmentSubmission;
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        try {
            $sql = $this->assignment->builder()->with([
                    'class_section.medium', 
                    'file', 
                    'class_subject.subject',
                    'assignment_commons.class_section.class',
                    'assignment_commons.class_section.section', 
                    'assignment_commons.class_section.medium',
                    'assignment_commons.class_subject',
                    'session_years_trackings'
                ])
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('id', 'LIKE', "%$search%")
                                ->orwhere('name', 'LIKE', "%$search%")
                                ->orwhere('type', 'LIKE', "%$search%")
                                ->orwhere('instructions', 'LIKE', "%$search%")
                                ->orwhere('points', 'LIKE', "%$search%")
                                ->orWhereHas('class_section.class', function ($q) use ($search) {
                                    $q->where('name', 'LIKE', "%$search%");
                                })->orWhereHas('class_section.section', function ($q) use ($search) {
                                    $q->where('name', 'LIKE', "%$search%");
                                })->orWhereHas('class_subject.subject', function ($q) use ($search) {
                                    $q->where('name', 'LIKE', "%$search%");
                                });
                        });
                    });
                })
                ->when(request('subject_id') != null, function ($query) {
                    $subject_id = request('subject_id');
                    $query->whereHas('assignment_commons', function ($query) use ($subject_id) {
                        $query->where('class_subject_id', $subject_id);
                    });
                })
                ->when(request('class_id') != null, function ($query) {
                    $class_id = request('class_id');
                    $query->whereHas('assignment_commons', function ($q) use ($class_id) {
                        $q->where('class_section_id', $class_id);
                    });
                })
                ->when(request('session_year_id') != null, function ($query) use ($request) {
                    $query->where('session_year_id', $request->session_year_id);
                });

            if(request('semester_id') != null) {
                $semester_id = request('semester_id');
                $sql = $sql->whereHas('session_years_trackings', function ($q) use ($semester_id ) {
                    $q->where('semester_id', $semester_id);
                });
            }

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();
            
            $rows = [];
            foreach ($res as $row) {
                $row = (object)$row;
                $assignmentCommons = $row->assignment_commons->map(function ($common) {
                    return $common->class_section ? $common->class_section->full_name : null;
                });
                
                $class_sections_list = $assignmentCommons->filter()->values()->toArray();
                
                $tempRow = $row->toArray();
                $tempRow['org_due_date'] = $row->getRawOriginal('due_date');
                $tempRow['class_section_with_medium'] = implode(', ', $class_sections_list);
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];
            
            return ResponseService::successResponse('Assignments fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffAssignmentApiController -> show");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-create');
        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit') ?? 20;
        
        $validator = Validator::make($request->all(), [
            "class_section_id"      => 'required|array',
            "class_section_id.*"    => 'numeric',
            "subject_id"            => 'required|numeric',
            "name"                  => 'required',
            "description"           => 'nullable',
            "due_date"              => 'required|date',
            "points"                => 'nullable|numeric',
            "resubmission"          => 'nullable|boolean',
            "extra_days_for_resubmission" => 'nullable|numeric',
            'file'                  => 'nullable|array',
            "type"                  => "required|in:homework,assignment",
            'file.*'                => ['mimes:jpeg,png,jpg,gif,svg,webp,pdf,doc,docx,xml', new MaxFileSize($file_upload_size_limit) ],
            'add_url'               => $request->has('add_url') && !empty($request->add_url) ? 'required' : 'nullable',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $sessionYear = $this->cache->getDefaultSessionYear();

            $assignmentData = array(
                'name'                        => $request->name,
                'description'                 => $request->description,
                'points'                      => $request->points,
                'instructions'                => $request->instructions ?? null,
                'due_date'                    => date('Y-m-d H:i', strtotime($request->due_date)),
                'resubmission'                => $request->resubmission ? 1 : 0,
                'extra_days_for_resubmission' => $request->resubmission ? $request->extra_days_for_resubmission : null,
                'session_year_id'             => $sessionYear->id,
                'type'                        => $request->type ?? 'homework',
                'created_by'                  => Auth::user()->id,
            );

            $section_ids = is_array($request->class_section_id) ? $request->class_section_id : [$request->class_section_id];
            
            // Get class_subject_id from the first class_section
            $subjectTeacher = null;
            if (count($section_ids) > 0) {
                $firstSectionId = $section_ids[0];
                $subjectTeacher = $this->subjectTeacher->builder()->where('class_section_id', $firstSectionId)->where('subject_id', $request->subject_id)->first();
                if (!$subjectTeacher) {
                    return ResponseService::errorResponse("Subject Teacher not mapped for this subject and class section");
                }
            }
            
            $assignmentData['class_subject_id'] = $subjectTeacher->class_subject_id;
            // The table schema actually has class_section_id on the assignment table as well (legacy). We store the first one there.
            $assignmentData['class_section_id'] = $section_ids[0];
            
            $assignment = $this->assignment->create($assignmentData);

            // Create assignment_commons for each section
            foreach ($section_ids as $section_id) {
                $subjectTeacherSection = $this->subjectTeacher->builder()->where('class_section_id', $section_id)->where('subject_id', $request->subject_id)->first();
                if ($subjectTeacherSection) {
                    $assignmentCommonData = [
                        'assignment_id' => $assignment->id,
                        'class_section_id' => $section_id,
                        'class_subject_id' => $subjectTeacherSection->class_subject_id
                    ];
                    $this->assignmentCommon->create($assignmentCommonData);
                }
            }
        
            // Handle File Upload
            if ($request->hasFile('file')) {
                $fileData = [];
                $assignmentModelAssociate = $this->files->model()->modal()->associate($assignment);
        
                foreach ($request->file('file') as $file_upload) {
                    $tempFileData = array(
                        'modal_type' => $assignmentModelAssociate->modal_type,
                        'modal_id'   => $assignmentModelAssociate->modal_id,
                        'file_name'  => $file_upload->getClientOriginalName(),
                        'type'       => 1,
                        'file_url'   => $file_upload, 
                    );
                    $fileData[] = $tempFileData;
                }
                $this->files->createBulk($fileData);
            }
        
            // Handle URL Upload
            if ($request->add_url) {
                $urlData = [];
                $urls = is_array($request->add_url) ? $request->add_url : [$request->add_url];
        
                foreach ($urls as $url) {
                    $urlParts = parse_url($url);
                    $fileName = basename($urlParts['path'] ?? '/');
                    if (empty($fileName) || $fileName == '/') $fileName = $url;
        
                    $assignmentModelAssociate = $this->files->model()->modal()->associate($assignment);
        
                    $tempUrlData = array(
                        'modal_type' => $assignmentModelAssociate->modal_type,
                        'modal_id'   => $assignmentModelAssociate->modal_id,
                        'file_name'  => $fileName, 
                        'type'       => 4,
                        'file_url'   => $url,
                    );
                    $urlData[] = $tempUrlData;
                }
                $this->files->createBulk($urlData);
            }
        
            // Send Notification
            $subjectName = $this->subject->builder()->select('name')->where('id', $request->subject_id)->pluck('name')->first();
            $title = 'New ' . $request->type . ' added in ' . $subjectName;
            $body = $request->name;
            $type = $request->type;
        
            $students = $this->student->builder()->whereIn('class_section_id', $section_ids)->get();
            $user = $students->pluck('user_id')->toArray();
        
            $semester = $this->cache->getDefaultSemesterData();
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Assignment', $assignment->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, $semester ? $semester->id : null);
            
            DB::commit();

            if (!empty($user)) {
                send_notification($user, $title, $body, $type);
            }
            return ResponseService::successResponse('Assignment Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                return ResponseService::warningResponse("Data Stored successfully. But App push notification not send.");
            }
            ResponseService::logErrorResponse($e, "StaffAssignmentApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function update($id, Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-edit');
        $file_upload_size_limit = $this->cache->getSystemSettings('file_upload_size_limit') ?? 20;
        
        $validator = Validator::make($request->all(), [
            "class_section_id"      => 'required|array',
            "class_section_id.*"    => 'numeric',
            "class_subject_id"      => 'required|numeric',
            "name"                  => 'required',
            "description"           => 'nullable',
            "due_date"              => 'required|date',
            "type"                  => "required|in:homework,assignment",
            "points"                => "required_if:type,assignment|nullable|numeric|min:0",
            "resubmission"          => 'nullable|boolean',
            "extra_days_for_resubmission" => 'nullable|numeric',
            'file'                  => 'nullable|array',
            'file.*'                => ['mimes:jpeg,png,jpg,gif,svg,webp,pdf,doc,docx,xml', new MaxFileSize($file_upload_size_limit) ],
            'add_url'               => $request->has('add_url') && !empty($request->add_url) ? 'nullable' : 'nullable',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $sessionYear = $this->cache->getDefaultSessionYear();
            
            $assignmentData = array(
                'name'                        => $request->name,
                'description'                 => $request->description,
                'points'                      => $request->points,
                'instructions'                => $request->instructions ?? null,
                'class_subject_id'            => $request->class_subject_id,
                'due_date'                    => date('Y-m-d H:i', strtotime($request->due_date)),
                'resubmission'                => $request->resubmission ? 1 : 0,
                'extra_days_for_resubmission' => $request->resubmission ? $request->extra_days_for_resubmission : null,
                'session_year_id'             => $sessionYear->id,
                'type'                        => $request->type ?? 'homework',
                'edited_by'                   => Auth::user()->id,
            );

            $section_ids = is_array($request->class_section_id) ? $request->class_section_id : [$request->class_section_id];
            $assignmentData['class_section_id'] = $section_ids[0]; // legacy main field

            $assignment = $this->assignment->update($id, $assignmentData);

            // Delete existing commons and re-insert
            $this->assignmentCommon->builder()->where('assignment_id', $id)->delete();
            foreach ($section_ids as $section_id) {
                // Determine class_subject_id dynamically based on the section
                $classID = $this->classSection->builder()->where('id', $section_id)->pluck('class_id')->first();
                $subject_id = $this->class_subjects->builder()->where('id', $request->class_subject_id)->pluck('subject_id')->first();
                
                $subjectTeacherSection = $this->subjectTeacher->builder()->where('class_section_id', $section_id)->where('subject_id', $subject_id)->first();
                if ($subjectTeacherSection) {
                    $assignmentCommonData = [
                        'assignment_id' => $assignment->id,
                        'class_section_id' => $section_id,
                        'class_subject_id' => $subjectTeacherSection->class_subject_id
                    ];
                    $this->assignmentCommon->create($assignmentCommonData);
                }
            }

            // Handle File Upload
            if ($request->hasFile('file')) {
                $fileData = [];
                $assignmentModelAssociate = $this->files->model()->modal()->associate($assignment);
        
                foreach ($request->file('file') as $file_upload) {
                    $tempFileData = array(
                        'modal_type' => $assignmentModelAssociate->modal_type,
                        'modal_id'   => $assignmentModelAssociate->modal_id,
                        'file_name'  => $file_upload->getClientOriginalName(),
                        'type'       => 1,
                        'file_url'   => $file_upload, 
                    );
                    $fileData[] = $tempFileData;
                }
                $this->files->createBulk($fileData);
            }
        
            // Handle URL Upload
            if ($request->add_url) {
                $urlData = [];
                $urls = is_array($request->add_url) ? $request->add_url : [$request->add_url];
        
                foreach ($urls as $url) {
                    $urlParts = parse_url($url);
                    $fileName = basename($urlParts['path'] ?? '/');
                    if (empty($fileName) || $fileName == '/') $fileName = $url;
        
                    $assignmentModelAssociate = $this->files->model()->modal()->associate($assignment);
        
                    $tempUrlData = array(
                        'modal_type' => $assignmentModelAssociate->modal_type,
                        'modal_id'   => $assignmentModelAssociate->modal_id,
                        'file_name'  => $fileName, 
                        'type'       => 4,
                        'file_url'   => $url,
                    );
                    $urlData[] = $tempUrlData;
                }
                $this->files->createBulk($urlData);
            }

            DB::commit();
            return ResponseService::successResponse('Assignment Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffAssignmentApiController -> update");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('assignment-delete');
        try {
            DB::beginTransaction();
            $assignment = $this->assignment->findById($id);

            // Delete files physically and records
            if ($assignment->file) {
                foreach ($assignment->file as $file) {
                    if ($file->type == 1 && Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }
                }
                $assignment->file()->delete();
            }

            // Remove associated common records
            $this->assignmentCommon->builder()->where('assignment_id', $id)->delete();

            // Track Deletion
            $sessionYear = $this->cache->getDefaultSessionYear();
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Assignment', $id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);

            $this->assignment->deleteById($id);
            DB::commit();
            
            return ResponseService::successResponse('Assignment Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffAssignmentApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }

    public function showAssignmentSubmissionDetails($id, Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-submission');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

        try {
            $sql = $this->assignmentSubmission->builder()
                ->with('assignment.class_subject.subject', 'student:first_name,last_name,id', 'file', 'session_year', 'assignment.class_section.class', 'assignment.class_section.medium')
                ->where('assignment_id', $id)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")
                            ->orwhere('points', 'LIKE', "%$search%")
                            ->orwhere('feedback', 'LIKE', "%$search%")
                            ->orWhereHas('student', function ($query) use ($search) {
                                $query->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'");
                            });
                    });
                });
            
            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = [
                'total' => $total,
                'data' => $res
            ];
            return ResponseService::successResponse('Assignment Submissions fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffAssignmentApiController -> showAssignmentSubmissionDetails");
            return ResponseService::errorResponse();
        }
    }

    public function bulkAssignmentSubmissionUpdate(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assignment-submission');
        $validator = Validator::make($request->all(), [
            'assignment_name' => 'required',
            'subject_name'    => 'required',
            'assignment_data' => 'required|array',
            'user_ids'        => 'required|string',
        ],[
            'user_ids.required' => 'Please select at least one student.',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        $userIds = array_filter(array_map('trim', explode(',', $request->input('user_ids'))));

        try {
            DB::beginTransaction();
            $assignmentSubmissionData = [];
            $acceptedStudentIds = [];
            $rejectedStudentIds = [];
            
            foreach ($request->assignment_data as $item) {
                if (in_array($item['id'], $userIds)) {
                    $assignmentSubmissionData[] = [
                        'id'         => $item['id'],
                        'student_id' => $item['student_id'],
                        'status'     => $item['status'],
                        'points'     => $item['points'] ?? '',
                        'feedback'   => $item['feedback'],
                    ];
                    
                    if ($item['status'] == 1) {
                        $acceptedStudentIds[] = (int)$item['student_id'];
                    } else {
                        $rejectedStudentIds[] = (int)$item['student_id'];
                    }
                }
            }

            if (!empty($assignmentSubmissionData)) {
                $this->assignmentSubmission->upsert($assignmentSubmissionData, ['id'], ['status', 'points', 'feedback']);
            }

            DB::commit();

            // Send Notifications
            if (!empty($acceptedStudentIds)) {
                $acceptedTitle = "Assignment accepted";
                $acceptedBody = $request->assignment_name . " accepted in " . $request->subject_name . " subject";
                send_notification($acceptedStudentIds, $acceptedTitle, $acceptedBody, 'assignment');
            }
            if (!empty($rejectedStudentIds)) {
                $rejectedTitle = "Assignment rejected";
                $rejectedBody = $request->assignment_name . " rejected in " . $request->subject_name . " subject";
                send_notification($rejectedStudentIds, $rejectedTitle, $rejectedBody, 'assignment');
            }
            
            return ResponseService::successResponse('Submissions evaluated successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffAssignmentApiController -> bulkAssignmentSubmissionUpdate");
            return ResponseService::errorResponse();
        }
    }
}

