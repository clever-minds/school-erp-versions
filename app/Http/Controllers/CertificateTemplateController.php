<?php

namespace App\Http\Controllers;

use App\Models\CertificateAssignment;
use App\Repositories\CertificateTemplate\CertificateTemplateInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\Exam\ExamInterface;
use App\Repositories\FormField\FormFieldsInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\GeneralFunctionService;
use App\Services\ResponseService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Support\Facades\Auth;

class CertificateTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    private CertificateTemplateInterface $certificateTemplate;
    private CachingService $cache;
    private UserInterface $user;
    private ClassSectionInterface $classSection;
    private ExamInterface $exam;
    private SessionYearInterface $sessionYear;
    private FormFieldsInterface $formFields;

    public function __construct(CertificateTemplateInterface $certificateTemplate, CachingService $cache, UserInterface $user, ClassSectionInterface $classSection, ExamInterface $exam, SessionYearInterface $sessionYear, FormFieldsInterface $formFields)
    {
        $this->certificateTemplate = $certificateTemplate;
        $this->cache = $cache;
        $this->user = $user;
        $this->classSection = $classSection;
        $this->exam = $exam;
        $this->sessionYear = $sessionYear;
        $this->formFields = $formFields;
    }

    public function index()
    {
        //
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noAnyPermissionThenRedirect(['certificate-create', 'certificate-list']);

        $formFields = $this->formFields->builder()->whereNot('type', 'file')->get();

        return view('certificate.template', compact('formFields'));
    }

    public function create()
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noAnyPermissionThenRedirect(['certificate-create']);
        $formFields = $this->formFields->builder()->whereNot('type', 'file')->get();

        $systemSettings = $this->cache->getSystemSettings();

        return view('certificate.builder', compact('formFields', 'systemSettings'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        ResponseService::noFeatureThenSendJson('ID Card - Certificate Generation');
        ResponseService::noPermissionThenSendJson('certificate-create');

        $request->validate([
            'name' => 'required',
            'layout' => 'required',
            'height' => 'required',
            'width' => 'required',
            'config_json' => 'required',
            'design_json' => 'required',
            'type' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $sessionYearId = $this->cache->getSessionYear()->id;

            $data = [
                'name' => $request->name,
                'layout' => $request->layout,
                'height' => $request->height,
                'width' => $request->width,
                'type' => $request->type,
                'config_json' => json_decode($request->config_json, true),
                'design_json' => json_decode($request->design_json, true),
                'session_year_id' => $sessionYearId
            ];
            if ($request->hasFile('background_image')) {
                $data['background_image'] = $request->background_image;
            }
            $this->certificateTemplate->create($data);
            DB::commit();
            ResponseService::successRedirectResponse(route('certificate-template.index'), 'Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Certificate Template Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenSendJson('certificate-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $sessionYearId = $this->cache->getSessionYear()->id;

        $sql = $this->certificateTemplate->builder()
            ->where('session_year_id', $sessionYearId)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orwhere('name', 'LIKE', "%$search%")
                        ->orwhere('type', 'LIKE', "%$search%");
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
            $operate = BootstrapTableService::button('fa fa-edit', route('certificate-template.edit', $row->id), ['btn-action-edit'], ['title' => trans('edit')]);
            $operate .= BootstrapTableService::deleteButton(route('certificate-template.destroy', $row->id));
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenRedirect('certificate-edit');
        $certificateTemplate = $this->certificateTemplate->findById($id);
        $formFields = $this->formFields->builder()->whereNot('type', 'file')->get();

        return view('certificate.builder', compact('certificateTemplate', 'formFields'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
        ResponseService::noFeatureThenSendJson('ID Card - Certificate Generation');
        ResponseService::noPermissionThenSendJson('certificate-edit');

        $request->validate([
            'name' => 'required',
            'layout' => 'required',
            'height' => 'required',
            'width' => 'required',
            'config_json' => 'required',
            'design_json' => 'required',
            'type' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $sessionYearId = $this->cache->getSessionYear()->id;

            $data = [
                'name' => $request->name,
                'layout' => $request->layout,
                'height' => $request->height,
                'width' => $request->width,
                'type' => $request->type,
                'config_json' => json_decode($request->config_json, true),
                'design_json' => json_decode($request->design_json, true),
                'session_year_id' => $sessionYearId
            ];

            if ($request->hasFile('background_image')) {
                $data['background_image'] = $request->background_image;
            }

            $this->certificateTemplate->update($id, $data);
            DB::commit();
            ResponseService::successRedirectResponse(route('certificate-template.index'), 'Data Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Certificate Template Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenSendJson('certificate-delete');
        try {
            DB::beginTransaction();
            $this->certificateTemplate->deleteById($id);
            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Certificate Template Controller -> Destroy Method");
            ResponseService::errorResponse();
        }
    }



    public function certificate()
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenRedirect('certificate-list');
        try {
            $classSections = $this->classSection->builder()->with('class.stream', 'class.shift', 'section', 'medium')->get()->pluck('full_name', 'id');

            $exams = $this->exam->builder()->with('class.medium')->where('publish', 1)->get()->append(['prefix_name']);
            $session_year_id = $this->cache->getSessionYear()->id;
            $certificateTemplates = $this->certificateTemplate->builder()->where('type', 'Student')->where('session_year_id', $session_year_id)->pluck('name', 'id');


            return view('certificate.student-list', compact('classSections', 'exams', 'certificateTemplates'));
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Certificate Template Controller -> Certificate Store Method");
            ResponseService::errorResponse();
        }
    }

    public function certificate_generate(Request $request)
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenRedirect('certificate-list');

        $request->validate([
            'certificate_template_id' => 'required',
            'ids' => 'required'
        ], [
            'certificate_template_id.required' => 'The certificate template field is required',
            'ids.required' => 'Please select at least one record.'
        ]);

        try {
            $assignmentIds = explode(",", $request->ids);

            $assignments = CertificateAssignment::with([
                'user.student.guardian',
                'user.staff',
                'user.roles',
                'user.extra_student_details.form_field',
                'certificate_template',
                'user.student.exam_result.exam',
                'exam',
                'session_year'
            ])
                ->with(['class_section' => function ($query) {
                    $query->with('class.stream', 'class.shift', 'section', 'medium');
                }])->whereIn('id', $assignmentIds)->get();

            if ($assignments->isEmpty()) {
                ResponseService::errorResponse('No valid assignments found.');
            }

            $certificateTemplate = $assignments->first()->certificate_template;
            if (!$certificateTemplate) {
                // Fallback if relation is somehow missing
                $certificateTemplate = $this->certificateTemplate->findById($request->certificate_template_id);
            }

            $height = $certificateTemplate->height * 3.7795275591;
            $width = $certificateTemplate->width * 3.7795275591;

            $layout = [
                'height' => $height . 'px',
                'width' => $width . 'px'
            ];

            foreach ($assignments as $assignment) {
                $user = $assignment->user;
                $processedData = [];
                $design_elements = $certificateTemplate->design_json['elements'] ?? [];

                foreach ($design_elements as $element) {
                    if (isset($element['content'])) {
                        $element['content'] = app(GeneralFunctionService::class)->replacePlaceholders($element['content'], $assignment);
                    }
                    if (isset($element['value'])) {
                        $element['value'] = app(GeneralFunctionService::class)->replacePlaceholders($element['value'], $assignment);
                    }
                    $processedData[] = $element;
                }

                $assignment->elements = $processedData;
                $assignment->image = $user->image ?? '';
            }

            $settings = $this->cache->getSchoolSettings();

            return view('certificate.certificate-pdf', compact('certificateTemplate', 'layout', 'assignments', 'settings'));
        } catch (\Throwable $e) {
            ResponseService::logErrorResponse($e, "Certificate Template Controller -> Certificate Generate Method");
            ResponseService::errorResponse();
        }
    }

    public function staff_certificate()
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenRedirect('certificate-list');
        try {
            $session_year_id = $this->cache->getSessionYear()->id;
            $certificateTemplates = $this->certificateTemplate->builder()->where('type', 'Staff')->where('session_year_id', $session_year_id)->pluck('name', 'id');

            return view('certificate.staff-list', compact('certificateTemplates'));
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Certificate Template Controller -> Staff Certificate Store Method");
            ResponseService::errorResponse();
        }
    }

    public function staff_generate_certificate(Request $request)
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenRedirect('certificate-list');

        $request->validate([
            'certificate_template_id' => 'required',
            'user_id' => 'required'
        ], [
            'certificate_template_id.required' => 'The certificate template field is required',
            'user_id.required' => 'Please select at least one record.'
        ]);

        try {

            $certificateTemplate = $this->certificateTemplate->findById($request->certificate_template_id);

            $height = $certificateTemplate->height * 3.7795275591;
            $width = $certificateTemplate->width * 3.7795275591;

            $layout = [
                'height' => $height . 'px',
                'width' => $width . 'px'
            ];

            $user_id = explode(",", $request->user_id);

            $users = $this->user->builder()->with('staff', 'roles', 'extra_student_details.form_field')->whereIn('id', $user_id)->get();
            $user_data = array();
            $design_elements = $certificateTemplate->design_json['elements'] ?? [];

            foreach ($users as $key => $user) {
                $processedData = [];
                foreach ($design_elements as $element) {
                    if (isset($element['content'])) {
                        $element['content'] = $this->replaceSatffPlaceholders($element['content'], $user);
                    }
                    if (isset($element['value'])) {
                        $element['value'] = $this->replaceSatffPlaceholders($element['value'], $user);
                    }
                    $processedData[] = $element;
                }

                $user_data[] = [
                    'image' => $user->image,
                    'elements' => $processedData
                ];
            }
            $users = $user_data;
            $settings = $this->cache->getSchoolSettings();

            return view('certificate.certificate-pdf', compact('certificateTemplate', 'layout', 'users', 'settings'));
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Certificate Template Controller -> Certificate Generate Store Method");
            ResponseService::errorResponse();
        }
    }

    private function replaceSatffPlaceholders($templateContent, $user)
    {
        $settings = $this->cache->getSchoolSettings();
        $sessionYear = $this->cache->getSessionYear();

        $today_date = Carbon::now();
        $joining_date = Carbon::createFromFormat($settings['date_format'] . ' ' . $settings['time_format'], $user->staff->joining_date)->format('Y-m-d');
        $joining_date = Carbon::parse($joining_date);

        $experience = $joining_date->diffInMonths($today_date);
        $experience = $experience / 12;

        // Define the placeholders and their replacements
        $placeholders = [
            '{full_name}' => $user->full_name,
            '{first_name}' => $user->first_name,
            '{last_name}' => $user->last_name,
            '{mobile}' => $user->mobile,
            '{dob}' => $user->dob,
            '{current_address}' => $user->current_address,
            '{permanent_address}' => $user->permanent_address,
            '{gender}' => $user->gender,
            '{email}' => $user->email,
            '{joining_date}' => date($settings['date_format'], strtotime($user->staff->joining_date)),
            '{role}' => implode(',', $user->roles->pluck('name')->toArray()),
            '{qualification}' => $user->staff->qualification,
            '{session_year}' => $sessionYear->name,
            '{experience}' => number_format($experience, 1),
            '{school_name}' => $settings['school_name'],
            '{issue_date}' => date('Y-m-d'),
            ...$this->extraFormFields($user)
            // Add more placeholders as needed
        ];

        // Replace the placeholders in the template content
        foreach ($placeholders as $placeholder => $replacement) {
            $templateContent = str_replace($placeholder, $replacement, $templateContent);
        }

        return $templateContent;
    }
}
