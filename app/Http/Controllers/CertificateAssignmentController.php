<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CertificateAssignment;
use App\Models\Staff;
use App\Models\User;
use App\Repositories\CertificateTemplate\CertificateTemplateInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\Exam\ExamInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\User\UserInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\BootstrapTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class CertificateAssignmentController extends Controller
{
    private CertificateTemplateInterface $certificateTemplate;
    private CachingService $cache;
    private ClassSectionInterface $classSection;
    private ExamInterface $exam;
    private UserInterface $user;
    private StudentInterface $student;

    public function __construct(
        CertificateTemplateInterface $certificateTemplate,
        CachingService $cache,
        ClassSectionInterface $classSection,
        ExamInterface $exam,
        UserInterface $user,
        StudentInterface $student
    ) {
        $this->certificateTemplate = $certificateTemplate;
        $this->cache               = $cache;
        $this->classSection        = $classSection;
        $this->exam                = $exam;
        $this->user                = $user;
        $this->student             = $student;
    }

    /**
     * Show the certificate assignment page.
     */
    public function index()
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noAnyPermissionThenRedirect(['certificate-list', 'certificate-create']);

        try {
            $sessionYearId = $this->cache->getSessionYear()->id;

            $studentTemplates = $this->certificateTemplate->builder()
                ->where('type', 'Student')
                ->where('session_year_id', $sessionYearId)
                ->pluck('name', 'id');

            $staffTemplates = $this->certificateTemplate->builder()
                ->where('type', 'Staff')
                ->where('session_year_id', $sessionYearId)
                ->pluck('name', 'id');

            $classSections = $this->classSection->builder()
                ->with('class.stream', 'class.shift', 'section', 'medium')
                ->get()
                ->pluck('full_name', 'id');

            $exams = $this->exam->builder()
                ->with('class.medium')
                ->where('publish', 1)
                ->get()
                ->append(['prefix_name']);

            return view('certificate.assign', compact(
                'studentTemplates',
                'staffTemplates',
                'classSections',
                'exams'
            ));
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'CertificateAssignmentController -> index');
            ResponseService::errorResponse();
        }
    }

    /**
     * AJAX: Return paginated list of students for the Bootstrap Table.
     */
    public function students(Request $request)
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenSendJson('certificate-list');

        $offset = $request->input('offset', 0);
        $limit  = $request->input('limit', 10);
        $sort   = $request->input('sort', 'id');
        $order  = $request->input('order', 'asc');
        $search = $request->input('search', '');

        $classSectionId = $request->input('class_section_id');
        $examId         = $request->input('exam_id');
        $templateId     = $request->input('certificate_template_id');

        $selectedSessionYearId = $this->cache->getSessionYear()->id;
        $defaultSessionYearId = $this->cache->getDefaultSessionYear()->id;
        $isCurrentSession = ($selectedSessionYearId == $defaultSessionYearId);

        if ($isCurrentSession) {
            $query = $this->student->builder()
                ->where('students.session_year_id', $selectedSessionYearId)
                ->select(
                    'students.*',
                    DB::raw("'current' as record_source"),
                    DB::raw("NULL as snapshot_roll_number"),
                    DB::raw("NULL as snapshot_class_section_id")
                );
        } else {
            $query = $this->student->builder()
                ->join('promote_students', 'students.user_id', '=', 'promote_students.student_id')
                ->where('promote_students.session_year_id', $selectedSessionYearId)
                ->select(
                    'students.*',
                    DB::raw("'historical' as record_source"),
                    'promote_students.roll_number as snapshot_roll_number',
                    'promote_students.class_section_id as snapshot_class_section_id'
                );
        }

        $query = $query->with([
            'promote_student' => function ($q) use ($selectedSessionYearId) {
                $q->where('session_year_id', $selectedSessionYearId)
                    ->with('class_section.class.stream', 'class_section.section', 'class_section.class.shift', 'class_section.medium');
            },
            'user.extra_student_details.form_field',
            'guardian',
            'class_section.class.stream',
            'class_section.section',
            'class_section.class.shift',
            'class_section.medium'
        ]);

        // Check if already assigned
        if ($templateId) {
            $assignedUserIds = CertificateAssignment::where('certificate_template_id', $templateId)
                ->pluck('user_id')
                ->toArray();
        }

        if ($classSectionId) {
            $query->where('class_section_id', $classSectionId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('roll_number', 'LIKE', "%$search%")
                    ->orWhere('admission_no', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'LIKE', "%$search%")
                            ->orwhere('last_name', 'LIKE', "%$search%")
                            ->orwhere('email', 'LIKE', "%$search%")
                            ->orwhere('dob', 'LIKE', "%$search%")
                            ->orWhereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'");
                    });
            });
        }

        $total = $query->count();
        $res  = $query->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        $bulkData = [];
        $bulkData['total'] = $total;

        $rows = [];
        $no = 1;

        foreach ($res as $row) {
            $operate = ''; // No action buttons needed for student selection list

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;

            if ($templateId) {
                $tempRow['already_assigned'] = in_array($row->user_id, $assignedUserIds, true);
            }

            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    /**
     * AJAX: Return paginated list of staff for the Bootstrap Table.
     */
    public function staffList(Request $request)
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenSendJson('certificate-list');

        $offset = $request->input('offset', 0);
        $limit  = $request->input('limit', 10);
        $sort   = $request->input('sort', 'id');
        $order  = $request->input('order', 'asc');
        $search = $request->input('search', '');
        $templateId = $request->input('certificate_template_id');

        $query = User::with('roles')->has('staff');

        if ($templateId) {
            $assignedUserIds = CertificateAssignment::where('certificate_template_id', $templateId)
                ->pluck('user_id')
                ->toArray();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        $total = $query->count();
        $res  = $query->orderBy($sort === 'full_name' ? 'users.first_name' : $sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        $bulkData = [];
        $bulkData['total'] = $total;

        $rows = [];
        $no = 1;

        foreach ($res as $row) {
            $operate = ''; // No action buttons needed for staff selection list

            $tempRow = $row->toArray();
            $tempRow['user_id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;

            if (isset($assignedUserIds)) {
                $tempRow['already_assigned'] = in_array($row->id, $assignedUserIds, true);
            }

            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    /**
     * Bulk assign certificate to selected users.
     */
    public function assign(Request $request)
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenSendJson('certificate-create');

        $request->validate([
            'certificate_template_id' => 'required|exists:certificate_templates,id',
            'user_ids'                => 'required|string',
            'user_type'               => 'required|in:Student,Staff',
            'issued_at'               => 'nullable',
        ]);

        try {
            $template   = $this->certificateTemplate->findById((int) $request->certificate_template_id);
            $userIds    = array_filter(array_unique(explode(',', $request->user_ids)));
            $userType   = $request->user_type;
            $issuedAt   = $request->issued_at ? date('Y-m-d', strtotime($request->issued_at)) : date('Y-m-d');
            $sessionYear = $this->cache->getSessionYear();

            if (empty($userIds)) {
                ResponseService::errorResponse('No users selected.');
            }

            DB::beginTransaction();

            $inserted = 0;
            $skipped  = 0;

            foreach ($userIds as $userId) {
                $userId = (int) $userId;

                $exists = CertificateAssignment::where('certificate_template_id', $template->id)
                    ->where('user_id', $userId)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $payload = [
                    'certificate_template_id' => $template->id,
                    'user_id'                 => $userId,
                    'user_type'               => $userType,
                    'session_year_id'         => $sessionYear->id,
                    'issued_at'               => $issuedAt,
                ];

                if ($userType === 'Student') {
                    $student = DB::table('students')->where('user_id', $userId)->first();
                    if ($student) {
                        $payload['class_section_id'] = $student->class_section_id;
                        $payload['roll_no']          = $student->roll_number;
                    }
                    if ($request->exam_id) {
                        $payload['exam_id'] = $request->exam_id;
                    }
                }

                CertificateAssignment::create($payload);
                $inserted++;
            }

            DB::commit();

            $msg = "{$inserted} certificate(s) assigned successfully.";
            if ($skipped > 0) {
                $msg .= " {$skipped} skipped (already assigned).";
            }

            ResponseService::successResponse($msg);
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'CertificateAssignmentController -> assign');
            ResponseService::errorResponse();
        }
    }

    /**
     * AJAX: Remove a certificate assignment.
     */
    public function revoke(Request $request)
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenSendJson('certificate-delete');

        $request->validate([
            'certificate_template_id' => 'required',
            'user_id'                 => 'required',
        ]);

        try {
            DB::beginTransaction();
            CertificateAssignment::where('certificate_template_id', $request->certificate_template_id)
                ->where('user_id', $request->user_id)
                ->delete();
            DB::commit();
            ResponseService::successResponse('Certificate assignment revoked.');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'CertificateAssignmentController -> revoke');
            ResponseService::errorResponse();
        }
    }

    /**
     * Show assignment history / log.
     */
    public function history(Request $request)
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenSendJson('certificate-list');

        $offset     = $request->input('offset', 0);
        $limit      = $request->input('limit', 10);
        $search     = $request->input('search', '');
        $templateId = $request->input('certificate_template_id');

        $sessionYear = $this->cache->getSessionYear();

        $query = CertificateAssignment::with(['user', 'certificate_template', 'class_section' => function ($q) {
            $q->with('class.stream', 'section', 'medium', 'class.shift');
        }]);

        $query = $query->where('session_year_id', $sessionYear->id);

        if ($templateId) {
            $query->where('certificate_template_id', $templateId);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%");
            });
        }

        $total = $query->count();
        $res = $query->latest()
            ->skip($offset)
            ->take($limit)
            ->get();

        $bulkData = [];
        $bulkData['total'] = $total;

        $rows = [];
        $no = 1;

        foreach ($res as $row) {
            // Let the frontend formatter handle Revoke as it relies on specific ids
            $operate = '';

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;

            $tempRow['id'] = $row->id;
            $tempRow['full_name'] = $row->user?->full_name;
            $tempRow['image'] = $row->user?->image;
            $tempRow['email'] = $row->user?->email;
            $tempRow['user_type'] = $row->user_type;
            $tempRow['template_name'] = $row->certificate_template?->name;
            $tempRow['class_section'] = $row->class_section?->full_name;
            $tempRow['roll_no'] = $row->roll_no;
            $tempRow['issued_at'] = $row->issued_at;
            $tempRow['user_id'] = $row->user_id;
            $tempRow['template_id'] = $row->certificate_template_id;

            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }
}
