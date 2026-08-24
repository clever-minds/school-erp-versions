<?php

namespace App\Http\Controllers;

use App\Repositories\CertificateTemplate\CertificateTemplateInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\Exam\ExamInterface;
use App\Repositories\FormField\FormFieldsInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassSection;
use Illuminate\Support\Str;
use NumberFormatter;

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
        ResponseService::noAnyPermissionThenRedirect(['certificate-create','certificate-list']);

        $formFields = $this->formFields->builder()->whereNot('type','file')->get();

        return view('certificate.template',compact('formFields'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
            'page_layout' => 'required',
            'height' => 'required',
            'width' => 'required',
            'user_image_shape' => 'required',
            'image_size' => 'required',
            'description' => 'required',
            'type' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $page_layout = 'A4 Landscape';
            if ($request->height == 210 && $request->width == 297) {
                // A4 Landscape
                $page_layout = 'A4 Landscape';
            } else if($request->height == 297 && $request->width == 210) {
                // A4 Portrait
                $page_layout = 'A4 Portrait';
            } else {
                // Custom
                $page_layout = 'Custom';
            }

            $data = [
                'name' => $request->name,
                'page_layout' => $page_layout,
                'height' => $request->height,
                'width' => $request->width,
                'user_image_shape' => $request->user_image_shape,
                'image_size' => $request->image_size,
                'description' => $request->description,
                'type' => $request->type,
            ];
            if ($request->hasFile('background_image')) {
                $data['background_image'] = $request->background_image;
            }
            $certificateTemplate = $this->certificateTemplate->create($data);
            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
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

        $sql = $this->certificateTemplate->builder()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                    ->orwhere('name', 'LIKE', "%$search%")
                    ->orwhere('type', 'LIKE', "%$search%");
                });
            });
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = BootstrapTableService::button('fa fa-edit',route('certificate-template.edit',$row->id),[ 'btn-gradient-primary'],['title' => trans('edit')]);
            $operate .= BootstrapTableService::button('fa fa-table-layout',route('certificate-template.design',$row->id),[ 'btn-gradient-info'],['title' => trans('layout')]);
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
        $formFields = $this->formFields->builder()->whereNot('type','file')->get();

        return view('certificate.edit-template',compact('certificateTemplate','formFields'));

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
            'page_layout' => 'required',
            'height' => 'required',
            'width' => 'required',
            'user_image_shape' => 'required',
            'image_size' => 'required',
            'description' => 'required',
            'type' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $page_layout = 'A4 Landscape';
            if ($request->height == 210 && $request->width == 297) {
                // A4 Landscape
                $page_layout = 'A4 Landscape';
            } else if($request->height == 297 && $request->width == 210) {
                // A4 Portrait
                $page_layout = 'A4 Portrait';
            } else {
                // Custom
                $page_layout = 'Custom';
            }
            $data = [
                'name' => $request->name,
                'page_layout' => $page_layout,
                'height' => $request->height,
                'width' => $request->width,
                'user_image_shape' => $request->user_image_shape,
                'image_size' => $request->image_size,
                'description' => $request->description,
                'type' => $request->type,
            ];

            if ($request->hasFile('background_image')) {
                $data['background_image'] = $request->background_image;
            }

            $this->certificateTemplate->update($id, $data);
            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
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

    public function design($id)
    {
        //
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenRedirect('certificate-edit');
        try {
            $certificateTemplate = $this->certificateTemplate->findById($id);
            $settings = $this->cache->getSchoolSettings();

            $style = json_decode($certificateTemplate->style, true);

            if (!isset($style['description'])) {
                $style['description'] = 'style="position:absolute; left: 145px;top: 255px"';
            }

            if (!isset($style['title'])) {
                $style['title'] = 'style="position:absolute; left: 145px;top: 290px"';
            }

            if (!isset($style['issue_date'])) {
                $style['issue_date'] = 'style="position:absolute; left: 100px;top: 100px"';
            }

            if (!isset($style['signature'])) {
                $style['signature'] = 'style="position:absolute; left: 150px;top: 150px"';
            }

            if (!isset($style['school_name'])) {
                $style['school_name'] = 'style="position:absolute; left: 480px;top: 60px"';
            }

            if (!isset($style['school_address'])) {
                $style['school_address'] = 'style="position:absolute; left: 125px;top: 85px"';
            }

            if (!isset($style['school_mobile'])) {
                $style['school_mobile'] = 'style="position:absolute; left: 125px;top: 130px"';
            }

            if (!isset($style['school_email'])) {
                $style['school_email'] = 'style="position:absolute; left: 125px;top: 175px"';
            }

            if (!isset($style['school_logo'])) {
                $style['school_logo'] = 'style="position:absolute; left: 525px;top: 75px"';
            }

            if (!isset($style['user_image'])) {
                $style['user_image'] = 'style="position:absolute; left: 525px;top: 125px"';
            }

            $height = $certificateTemplate->height * 3.7795275591;
            $width = $certificateTemplate->width * 3.7795275591;

            $layout = [
                'height' => $height.'px',
                'width' => $width.'px'
            ];
            
            
            return view('certificate.design',compact('certificateTemplate','settings','style','layout'));
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Certificate Template Controller -> Design Method");
            ResponseService::errorResponse();
        }
    }

    public function design_store(Request $request, $id)
    {
        //
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenRedirect('certificate-edit');
        try {
            
            $fields = '';
            if ($request->school_data) {
                $fields = implode(",",$request->school_data);    
            }
            

            $style = array();
            foreach ($request->style as $key => $value) {
                $style[$key] = $value;
            }
            $value = [
                'style' => $style,
                'fields' => $fields
            ];
            $this->certificateTemplate->update($id, $value);

            ResponseService::successResponse('Data Updated Successfully');
            
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Certificate Template Controller -> Design Store Method");
            ResponseService::errorResponse();
        }
    }

    public function certificate()
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenRedirect('certificate-list');
        try {
            $classSections = $this->classSection->builder()->with('class.stream','section','medium')->get()->pluck('full_name','id');

            $exams = $this->exam->builder()->with('class.medium')->where('publish', 1)->get()->append(['prefix_name']);
            $certificateTemplates = $this->certificateTemplate->builder()->whereNotNull('style')->where('type' ,'Student')->pluck('name','id');

            $sessionYears = $this->sessionYear->builder()->pluck('name','id');

            return view('certificate.student-list', compact('classSections','exams','certificateTemplates','sessionYears'));
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
            'user_id' => 'required'
        ],[
            'certificate_template_id.required' => 'The certificate template field is required',
            'user_id.required' => 'Please select at least one record.'
        ]);

        try {
            
            $certificateTemplate = $this->certificateTemplate->findById($request->certificate_template_id);

            $height = $certificateTemplate->height * 3.7795275591;
            $width = $certificateTemplate->width * 3.7795275591;

            $layout = [
                'height' => $height.'px',
                'width' => $width.'px'
            ];
            
            $user_id = explode(",",$request->user_id);

            $users = $this->user->builder()->with(['student' => function($q) use($request) {
                $q->with('class_section.class.stream','class_section.section','class_section.medium','guardian')
                ->when($request->exam_id, function($q) use($request) {
                    $q->with(['exam_result' => function($q) use($request) {
                        $q->where('exam_id', $request->exam_id)->with('exam:id,name');
                    }]);
                });
            }])->whereIn('id',$user_id)->with('extra_student_details.form_field')->get();
            $user_data = array();
            foreach ($users as $key => $user) {
                $user_data[] = [
                    'image' => $user->image,
                    'description' => $this->replacePlaceholders($certificateTemplate->description, $user, $request->exam_id)
                ];
            }
            $users = $user_data;
            $style = json_decode($certificateTemplate->style, true);
            $settings = $this->cache->getSchoolSettings();
            

            return view('certificate.certificate-pdf',compact('certificateTemplate','layout','users','style','settings'));
                        
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Certificate Template Controller -> Certificate Generate Store Method");
            ResponseService::errorResponse();
        }
    }

    private function replacePlaceholders($templateContent, $user, $exam_id = null)
    {
        $settings = $this->cache->getSchoolSettings();
        $sessionYear = $this->cache->getDefaultSessionYear();
        // Define the placeholders and their replacements
       // dd($user->student->attendance()->where('type',1)->count());
        $dobWords = $this->dobToWords($user->dob);
        // Pehli attendance record
      $firstAttendance = $user->student->attendance1()
    ->orderBy('date', 'asc')
    ->first();
        $admissionClass = '';

        if ($firstAttendance) {
            $classSection = ClassSection::with('class', 'section')
                ->where('id', $firstAttendance->class_section_id)
                ->first();

            $admissionClass = $classSection?->full_name ?? '';
        }      
        $lastAttendance = $user->student->attendance1()
                ->orderBy('date', 'desc')
                ->first();
        $lastStudiedClassFigure = '';
        $lastStudiedClassWords = '';
        $dob_in_words = '';
      if ($lastAttendance) {

        $classSection = ClassSection::with('class')->find($lastAttendance->class_section_id);

        $lastStudiedClassFigure = '';
        $lastStudiedClassWords  = '';
        $dob_in_words = '';
        if ($classSection && $classSection->class) {

            $className = trim($classSection->class->name);

            $lastStudiedClassFigure = $className;

            if (is_numeric($className)) {
                $fmt = new NumberFormatter('en', NumberFormatter::SPELLOUT);
                $lastStudiedClassWords = Str::ucfirst($fmt->format((int)$className));
            } else {
                $lastStudiedClassWords = ucwords(strtolower($className));
            }
        }
        if ($classSection && $classSection->class) {

            $className = trim($classSection->class->name);

            $lastStudiedClassFigure = $className;

            if (is_numeric($className)) {
                $fmt = new NumberFormatter('en', NumberFormatter::SPELLOUT);
                $lastStudiedClassWords = Str::ucfirst($fmt->format((int)$className));
            } else {
                $lastStudiedClassWords = ucwords(strtolower($className));
            }
        }
    }
    if ($user->dob) {
        $dob_in_words = $this->dobToWords($user->dob);
    }
       

        $lastExam = $user->student->exam_result()
            ->orderBy('created_at', 'desc')
            ->first();
             $latestExam = $user->student->exam_result()
            ->orderBy('id', 'desc')
            ->first();
        $lastExamResult = $lastExam->result ?? '';
        $qualifiedForHigherClass = (strtolower($lastExamResult) === 'pass') ? 'Yes' : 'No';     

        $placeholders = [
            '{dob_in_words}'=>$dob_in_words,
            '{dob_words}'=>$dob_in_words,
            '{full_name}' => $user->full_name,
            '{first_name}' => $user->first_name,
            '{last_name}' => $user->last_name,
            '{class_section}' => $user->student->class_section->full_name,
            '{student_mobile}' => $user->mobile,
            '{dob}' => date($settings['date_format'],strtotime($user->dob)),

            '{roll_no}' => $user->student->roll_number,
            '{admission_no}' => $user->student->admission_no,
            '{current_address}' => $user->current_address,
            '{permanent_address}' => $user->permanent_address,
            '{gender}' => $user->gender,
            '{nationality}' => $user->student->nationality,
             '{dise_code}' => $settings['dise_code'] ?? '',
             "{promotion_class_figure}"=>"",
             "{promotion_class_words}"=>"",
             '{certificate_no}' => '',
             '{from}' => $firstAttendance ? date($settings['date_format'], strtotime($firstAttendance->date)) : '',
             '{reason_for_leaving}'=> "",
            '{birth_place}' => $user->student->birth_place,
            '{remark}' => $user->student->remarks,
            '{application_date}' => date('d-m-Y'),
            '{date_of_issue}'    => date('d-m-Y'),
            '{admission_date}' => date($settings['date_format'],strtotime($user->student->admission_date)),
            '{guardian_name}' => $user->student->guardian->first_name.' '.$user->student->guardian->last_name,
            '{mother_name}' => $user->student->guardian->mother_name,
            '{caste_subcaste}' => $user->student->cast,
            '{guardian_mobile}' => $user->student->guardian->mobile,
            '{guardian_email}' => $user->student->guardian->email,
            '{session_year}' => $sessionYear->name,
            '{attendance_days}' => $user->student->attendance1()->where('type', 1)->count(),
            '{attendance_out_of}' => $user->student->attendance1()->count(),
            '{last_school_attended}'=> optional($user->student->attendance1()->where('type', 1)->latest('created_at') ->first() )->created_at?? '',
            '{fees_paid_till}' => optional( $user->student->fees_paid()->latest('date')->first())->date ?? '',
            '{admission_date_with_class}'=> date($settings['date_format'], strtotime($user->student->admission_date))
        . ' - Class: ' . ($admissionClass ?: 'N/A'),
            '{class}'  => $user->student->class_section->class->name,
            '{student_uid}'=>$user->student->uni_no,
            '{last_studied_class_figure}' => $lastStudiedClassFigure,
            '{last_exam_result}'          => $lastExamResult,
            '{last_studied_class_words}'  => $lastStudiedClassWords,
            '{qualified_for_higher_class}' => $qualifiedForHigherClass,

            // Jiam specific placeholders
            '{jiam_school_logo}' => '',
            '{jiam_school_address}' => '',
            '{jiam_school_mobile}' => '',
            ...$this->extraFormFields($user)
            // Add more placeholders as needed
        ];

        $isJiamStudent = false;
        // Placeholder for identifying Jiam students.
        // TODO: Ask user how to identify Jiam students (e.g., class name, custom field)
        if (str_contains(strtolower($user->student->class_section->full_name), 'jiam')) {
            $isJiamStudent = true;
        }

        if ($isJiamStudent) {
            $placeholders['{jiam_school_logo}'] = asset('images/Jiam Logo.png');
            $placeholders['{jiam_school_address}'] = 'Jiam English Medium School Tandalja, Vadodara';
            $placeholders['{jiam_school_mobile}'] = 'MOBILE_NUMBER_TO_BE_PROVIDED_BY_USER'; // Placeholder, will ask user
        } else {
            $placeholders['{jiam_school_logo}'] = '';
            $placeholders['{jiam_school_address}'] = '';
            $placeholders['{jiam_school_mobile}'] = '';
        }
        $exam_data = array();

        if ($exam_id && count($user->student->exam_result)) {
            $result = $user->student->exam_result[0];
            $exam_data = [
                '{exam}' => $result->exam->name,
                '{total_marks}' => $result->total_marks,
                '{obtain_marks}' => $result->obtained_marks,
                '{grade}' => $result->grade,
            ];
        }

        $placeholders = array_merge($placeholders, $exam_data);

        // Replace the placeholders in the template content
        foreach ($placeholders as $placeholder => $replacement) {
            $templateContent = str_replace($placeholder, $replacement, $templateContent);
        }

        return $templateContent;
    }

    public function extraFormFields($user)
    {
        $extraStudentDetails = array();
        foreach ($user->extra_student_details as $key => $formField) {
            if (in_array($formField->form_field->type, ['radio','text','number','textarea'])) {
                $extraStudentDetails['{'.$formField->form_field->name.'}'] = $formField->data;
            }
            if ($formField->form_field->type == 'checkbox') {
                $data = json_decode($formField->data);
                if ($data) {
                    $extraStudentDetails['{'.$formField->form_field->name.'}'] = implode(", ", $data);
                }
            }
            if ($formField->form_field->type == 'dropdown') {
                if($formField->form_field && isset($formField->form_field->default_values[$formField->data])) {
                    $extraStudentDetails['{'.$formField->form_field->name.'}'] = $formField->form_field->default_values[$formField->data];
                }
            }
        }
        return $extraStudentDetails;
    }

    public function staff_certificate()
    {
        ResponseService::noFeatureThenRedirect('ID Card - Certificate Generation');
        ResponseService::noPermissionThenRedirect('certificate-list');
        try {
            $certificateTemplates = $this->certificateTemplate->builder()->whereNotNull('style')->where('type' ,'Staff')->pluck('name','id');

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
        ],[
            'certificate_template_id.required' => 'The certificate template field is required',
            'user_id.required' => 'Please select at least one record.'
        ]);

        try {
            
            $certificateTemplate = $this->certificateTemplate->findById($request->certificate_template_id);

            $height = $certificateTemplate->height * 3.7795275591;
            $width = $certificateTemplate->width * 3.7795275591;

            $layout = [
                'height' => $height.'px',
                'width' => $width.'px'
            ];
            
            $user_id = explode(",",$request->user_id);

            $users = $this->user->builder()->with('staff','roles','extra_student_details.form_field')->whereIn('id',$user_id)->get();
            $user_data = array();
            foreach ($users as $key => $user) {
                $user_data[] = [
                    'image' => $user->image,
                    'description' => $this->replaceSatffPlaceholders($certificateTemplate->description, $user)
                ];
            }
            $users = $user_data;
            $style = json_decode($certificateTemplate->style, true);
            $settings = $this->cache->getSchoolSettings();
            

            return view('certificate.certificate-pdf',compact('certificateTemplate','layout','users','style','settings'));
                        
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Certificate Template Controller -> Certificate Generate Store Method");
            ResponseService::errorResponse();
        }
    }

    private function replaceSatffPlaceholders($templateContent, $user)
    {
        $settings = $this->cache->getSchoolSettings();
        $sessionYear = $this->cache->getDefaultSessionYear();

        $today_date = Carbon::now();
        $joining_date = Carbon::parse($user->staff->joining_date);

        $experience = $joining_date->diffInMonths($today_date);
        $experience = $experience / 12;

        // Define the placeholders and their replacements
        $dobWords = $this->dobToWords($user->dob);

        $placeholders = [
            '{dob_words}' => $dobWords,
            '{full_name}' => $user->full_name,
            '{first_name}' => $user->first_name,
            '{last_name}' => $user->last_name,
            '{mobile}' => $user->mobile,
            '{dob}' => date($settings['date_format'],strtotime($user->dob)),
            '{current_address}' => $user->current_address,
            '{permanent_address}' => $user->permanent_address,
            '{gender}' => $user->gender,
            '{email}' => $user->email,
            '{joining_date}' => date($settings['date_format'],strtotime($user->staff->joining_date)),
            '{role}' => implode(',',$user->roles->pluck('name')->toArray()),
            '{qualification}' => $user->staff->qualification,
            '{session_year}' => $sessionYear->name,
            '{experience}' => number_format($experience, 1),
            ...$this->extraFormFields($user)
            // Add more placeholders as needed
        ];

        // Replace the placeholders in the template content
        foreach ($placeholders as $placeholder => $replacement) {
            $templateContent = str_replace($placeholder, $replacement, $templateContent);
        }

        return $templateContent;
    }

private function numberToWords($num)
{
    $ones = [
        "", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine",
        "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen",
        "Seventeen", "Eighteen", "Nineteen"
    ];

    $tens = [
        "", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"
    ];

    if ($num < 20) {
        return $ones[$num];
    }

    if ($num < 100) {
        return $tens[intdiv($num, 10)] . (($num % 10) ? " " . $ones[$num % 10] : "");
    }

    return $num; // fallback
}

private function dobToWords($dob)
{
    if (!$dob) return '';

    $date = date('d-m-Y', strtotime($dob));
    list($day, $month, $year) = explode('-', $date);

    $months = [
        "01" => "January", "02" => "February", "03" => "March",
        "04" => "April", "05" => "May", "06" => "June",
        "07" => "July", "08" => "August", "09" => "September",
        "10" => "October", "11" => "November", "12" => "December"
    ];

    $dayWords = $this->numberToWords(intval($day));
    $monthWords = $months[$month];
    $yearWords = $this->yearToWords($year);

    return "$dayWords $monthWords $yearWords";
}

private function yearToWords($year)
{
    $y = str_split($year, 2);

    return $this->numberToWords(intval($y[0])) . " " . $this->numberToWords(intval($y[1]));
}

}
