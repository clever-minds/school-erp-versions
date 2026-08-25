<?php

namespace App\Services;

use App\Repositories\ExtraFormField\ExtraFormFieldsInterface;
use App\Repositories\FeesPaid\FeesPaidInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\User\UserInterface;
use App\Repositories\StudentSubject\StudentSubjectInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use JsonException;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;

class UserService
{
    private UserInterface $user;
    private StudentInterface $student;
    private ExtraFormFieldsInterface $extraFormFields;
    private StudentSubjectInterface $studentSubject;
    private FeesPaidInterface $feesPaid;
    private CachingService $cache;

    public function __construct(UserInterface $user, StudentInterface $student, ExtraFormFieldsInterface $extraFormFields, StudentSubjectInterface $studentSubject, FeesPaidInterface $feesPaid, CachingService $cache)
    {
        $this->user = $user;
        $this->student = $student;
        $this->extraFormFields = $extraFormFields;
        $this->studentSubject = $studentSubject;
        $this->feesPaid = $feesPaid;
        $this->cache = $cache;
    }

    /**
     * @param $mobile
     * @return string
     */
    public function makeParentPassword($mobile)
    {
        return $mobile;
    }

    /**
     * @param $dob
     * @return string
     */
    public function makeStudentPassword($dob)
    {
        return str_replace('-', '', date('d-m-Y', strtotime($dob)));
    }

    /**
     * @param $first_name
     * @param $last_name
     * @param $email
     * @param $mobile
     * @param $gender
     * @param null $image
     * @return Model|null
     */
    public function createOrUpdateParent($first_name, $last_name, $email, $mobile, $gender, $image = null, $reset_password = null)
    {
        $password = $this->makeParentPassword($mobile);

        $parent = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'mobile'     => $mobile,
            'gender'     => $gender,
            'school_id'  => Auth::user()->school_id
        );

        //NOTE : This line will return the old values if the user is already exists
        $user = $this->user->guardian()->where('email', $email)->first();
        if (!empty($image)) {
            $parent['image'] = UploadService::upload($image, 'guardian');
        }

        if ($user) {
            $user->deleted_at = null;
            $user->save();

            if ($user->roles()->exists() && Auth::user()->roles && !in_array('Guardian', $user->roles->pluck('name')->toArray())) {
                return null;
            }
        }



        if (!empty($user)) {
            if (isset($parent['image'])) {
                if ($user->getRawOriginal('image') && Storage::disk('public')->exists($user->getRawOriginal('image'))) {
                    Storage::disk('public')->delete($user->getRawOriginal('image'));
                }
            }
            if ($reset_password) {
                $parent['password'] = Hash::make($password);
            }
            $user->assignRole('Guardian');

            $user->update($parent);
        } else {
            $parent['password'] = Hash::make($password);
            $parent['email'] = $email;
            $user = $this->user->create($parent);
            $user->assignRole('Guardian');
        }

        return $user;
    }

    /**
     * @param string $first_name
     * @param string $last_name
     * @param string $admission_no
     * @param string|null $mobile
     * @param string $dob
     * @param string $gender
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile|null $image
     * @param int $classSectionID
     * @param string $admissionDate
     * @param null $current_address
     * @param null $permanent_address
     * @param int $sessionYearID
     * @param int $guardianID
     * @param array $extraFields
     * @param int $status
     * @return Model|null
     * @throws JsonException
     * @throws Throwable
     */

    public function createStudentUser(string $first_name, string $last_name, string $admission_no, string|null $mobile, string $dob, string $gender, \Symfony\Component\HttpFoundation\File\UploadedFile|null $image, int $classSectionID, string $admissionDate, $current_address = null, $permanent_address = null, int $sessionYearID, int $guardianID, array $extraFields = [], int $status, $is_send_notification = null)
    {
        $password = $this->makeStudentPassword($dob);
        //Create Student User First
        $user = $this->user->create([
            'first_name'        => $first_name,
            'last_name'         => $last_name,
            'email'             => $admission_no,
            'mobile'            => $mobile,
            'dob'               => date('Y-m-d', strtotime($dob)),
            'gender'            => $gender,
            'password'          => Hash::make($password),
            'school_id'         => Auth::user()->school_id,
            'image'             => $image,
            'status'            => $status,
            'current_address'   => $current_address,
            'permanent_address' => $permanent_address,
            'deleted_at'        => $status == 1 ? null : '1970-01-01 01:00:00'
        ]);
        $user->assignRole('Student');

        $roll_number_db = $this->student->builder()->select(DB::raw('max(roll_number)'))->where('class_section_id', $classSectionID)->first();
        $roll_number_db = $roll_number_db['max(roll_number)'];
        $roll_number = $roll_number_db + 1;

        $student = $this->student->updateOrCreate(['user_id' => $user->id], [
            'user_id'          => $user->id,
            'class_section_id' => $classSectionID,
            'admission_no'     => $admission_no,
            'roll_number'      => $roll_number,
            'admission_date'   => date('Y-m-d', strtotime($admissionDate)),
            'guardian_id'      => $guardianID,
            'session_year_id'  => $sessionYearID,
            'join_session_year_id' => $sessionYearID,
            'leave_session_year_id' => null
        ]);

        // Store Extra Details
        $extraDetails = array();
        foreach ($extraFields as $fields) {
            $data = null;
            if (isset($fields['data'])) {
                $data = (is_array($fields['data']) ? json_encode($fields['data'], JSON_THROW_ON_ERROR) : $fields['data']);
            }
            $extraDetails[] = array(
                'user_id'    => $student->user_id,
                'form_field_id' => $fields['form_field_id'],
                'data'          => $data,
            );
        }
        if (!empty($extraDetails)) {
            $this->extraFormFields->createBulk($extraDetails);
        }

        $guardian = $this->user->guardian()->where('id', $guardianID)->firstOrFail();
        if (is_object($guardian)) {
            $guardian = (object) $guardian->toArray();
        }

        $parentPassword = $this->makeParentPassword($guardian->mobile);
        if ($is_send_notification) {
            $this->sendRegistrationEmail($guardian, $user, $student->admission_no, $password);
        }
        return $user;
    }

    /**
     * @param $userID
     * @param $first_name
     * @param $last_name
     * @param $mobile
     * @param $dob
     * @param $gender
     * @param $image
     * @param $sessionYearID
     * @param array $extraFields
     * @param null $guardianID
     * @param null $current_address
     * @param null $permanent_address
     * @return Model|null
     * @throws JsonException
     */
    public function updateStudentUser($userID, $first_name, $last_name, $mobile, $dob, $gender, $image, $sessionYearID, array $extraFields = [], $guardianID = null, $current_address = null, $permanent_address = null, $reset_password = null, $classSectionID)
    {
        $studentUserData = array(
            'first_name'        => $first_name,
            'last_name'         => $last_name,
            'mobile'            => $mobile,
            'dob'               => date('Y-m-d', strtotime($dob)),
            'current_address'   => $current_address,
            'permanent_address' => $permanent_address,
            'gender'            => $gender,
        );

        if (!empty($current_address)) {
            $studentUserData['current_address'] = $current_address;
        }

        if (!empty($permanent_address)) {
            $studentUserData['permanent_address'] = $permanent_address;
        }

        if (isset($reset_password)) {
            $studentUserData['password'] = Hash::make($this->makeStudentPassword($dob));
        }


        if ($image) {
            $studentUserData['image'] = $image;
        }
        //Create Student User First
        $user = $this->user->update($userID, $studentUserData);

        $studentDetail = $this->student->builder()->where('user_id', $userID)->first();
        if ($studentDetail) {
            if ($studentDetail->class_section_id != $classSectionID) {
                $studentSubject = $this->studentSubject->builder()->where('student_id', $userID)->get();
                if ($studentSubject->count() > 0) {
                    foreach ($studentSubject as $subject) {
                        $subject->delete();
                    }
                }
            }
        }

        $studentData = array(
            'guardian_id'     => $guardianID,
            'session_year_id' => $sessionYearID,
            'class_section_id' => $classSectionID
        );

        $student = $this->student->update($user->student->id, $studentData);
        $extraDetails = [];
        foreach ($extraFields as $fields) {
            if ($fields['input_type'] == 'file') {
                if (isset($fields['data']) && $fields['data'] instanceof UploadedFile) {
                    $extraDetails[] = array(
                        'id'            => $fields['id'],
                        'user_id'    => $student->user_id,
                        'form_field_id' => $fields['form_field_id'],
                        'data'          => $fields['data']
                    );
                }
            } else {
                $data = null;
                if (isset($fields['data'])) {
                    $data = (is_array($fields['data']) ? json_encode($fields['data'], JSON_THROW_ON_ERROR) : $fields['data']);
                }
                $extraDetails[] = array(
                    'id'            => $fields['id'],
                    'user_id'    => $student->user_id,
                    'form_field_id' => $fields['form_field_id'],
                    'data'          => $data,
                );
            }
        }
        $this->extraFormFields->upsert($extraDetails, ['id'], ['data']);
        $user->assignRole('Student');
        DB::commit();
        return $user;
    }

    /**
     * @param $email
     * @param $name
     * @param $plainTextPassword
     * @param $childName
     * @param $childAdmissionNumber
     * @param $childPlainTextPassword
     * @return void
     * @throws Throwable
     */
    public function sendRegistrationEmail($guardian, $child, $childAdmissionNumber, $childPlainTextPassword)
    {
        try {


            $school_name = Auth::user()->school->name;

            $email_body = $this->replacePlaceholders($guardian, $child, $childAdmissionNumber, $childPlainTextPassword);
            $data = [
                'subject'                => 'Admission Application Approved - Welcome to ' . $school_name,
                'email'                  => $guardian->email,
                'email_body'             => $email_body
            ];

            Mail::send('students.email', $data, static function ($message) use ($data) {
                $message->to($data['email'])->subject($data['subject']);
            });
        } catch (\Throwable $th) {
            if (Str::contains($th->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager', 'smtp'])) {
                ResponseService::warningResponse("Data has been stored successfully, but the email could not be sent.");
            } else {
                Log::error($th->getMessage());
                ResponseService::errorResponse(trans('error_occured'));
            }
        }
    }

    private function replacePlaceholders($guardian, $child, $childAdmissionNumber, $childPlainTextPassword)
    {

        $cache = app(CachingService::class);
        $schoolSettings = $cache->getSchoolSettings();
        $systemSettings = $cache->getSystemSettings();

        $templateContent = $schoolSettings['email-template-parent'] ?? '';
        // Define the placeholders and their replacements
        $placeholders = [
            '{parent_name}' => $guardian->full_name,
            '{code}' => Auth::user()->school->code,
            '{email}' => $guardian->email,
            '{password}' => $guardian->mobile,
            '{school_name}' => $schoolSettings['school_name'],

            '{child_name}' => $child->full_name,
            '{grno}' => $child->email,
            '{child_password}' => $childPlainTextPassword,
            '{admission_no}' => $childAdmissionNumber,

            '{support_email}' => $schoolSettings['school_email'] ?? '',
            '{support_contact}' => $schoolSettings['school_phone'] ?? '',

            '{android_app}' => $systemSettings['app_link'] ?? '',
            '{ios_app}' => $systemSettings['ios_app_link'] ?? '',

            // Add more placeholders as needed
        ];

        // Replace the placeholders in the template content
        foreach ($placeholders as $placeholder => $replacement) {
            $templateContent = str_replace($placeholder, $replacement, $templateContent);
        }

        return $templateContent;
    }

    public function sendStaffRegistrationEmail($user, $password)
    {
        try {
            $cache = app(CachingService::class);
            $schoolSettings = $cache->getSchoolSettings();
            $email_body = $this->replaceStaffPlaceholders($user, $password, $schoolSettings);
            $data = [
                'subject'     => 'Welcome to ' . $schoolSettings['school_name'],
                'email'       => $user->email,
                'email_body'  => $email_body
            ];

            Mail::send('teacher.email', $data, static function ($message) use ($data) {
                $message->to($data['email'])->subject($data['subject']);
            });
        } catch (\Throwable $th) {
            if (Str::contains($th->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager'])) {
                ResponseService::warningResponse("Data has been stored successfully, but the email could not be sent.");
            } else {
                ResponseService::errorResponse(trans('error_occured'));
            }
        }
    }

    private function replaceStaffPlaceholders($user, $password, $schoolSettings)
    {

        $cache = app(CachingService::class);
        $systemSettings = $cache->getSystemSettings();

        $templateContent = $schoolSettings['email-template-staff'] ?? '';
        // Define the placeholders and their replacements
        $placeholders = [
            '{full_name}' => $user->full_name,
            '{code}' => Auth::user()->school->code,
            '{email}' => $user->email,
            '{password}' => $password,
            '{school_name}' => $schoolSettings['school_name'],

            '{support_email}' => $schoolSettings['school_email'] ?? '',
            '{support_contact}' => $schoolSettings['school_phone'] ?? '',

            '{url}' => url('/'),

            '{android_app}' => $systemSettings['app_link'] ?? '',
            '{ios_app}' => $systemSettings['ios_app_link'] ?? '',

            // Add more placeholders as needed
        ];

        // Replace the placeholders in the template content
        foreach ($placeholders as $placeholder => $replacement) {
            $templateContent = str_replace($placeholder, $replacement, $templateContent);
        }

        return $templateContent;
    }

    public function sendApplicationRejectEmail($user, $class_name, $guardian)
    {
        try {
            $cache = app(CachingService::class);
            $schoolSettings = $cache->getSchoolSettings();
            $email_body = $this->replaceApplicationRejectPlaceholders($user, $class_name, $schoolSettings, $guardian);
            $data = [
                'subject'     => 'Admission Application Rejected - ' . $schoolSettings['school_name'],
                'email'       => $guardian->email,
                'email_body'  => $email_body
            ];

            Mail::send('students.email', $data, static function ($message) use ($data) {
                $message->to($data['email'])->subject($data['subject']);
            });
        } catch (\Throwable $th) {
            if (Str::contains($th->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager'])) {
                ResponseService::warningResponse("Data has been stored successfully, but the email could not be sent.");
            } else {
                ResponseService::errorResponse(trans('error_occured'));
            }
        }
    }

    private function replaceApplicationRejectPlaceholders($user, $class_name, $schoolSettings, $guardian)
    {
        $cache = app(CachingService::class);
        $systemSettings = $cache->getSystemSettings();

        $templateContent = $schoolSettings['email-template-application-reject'] ?? '';
        // Define the placeholders and their replacements
        $placeholders = [
            '{parent_name}' => $guardian->full_name,
            '{child_name}' => $user->full_name,
            '{school_name}' => $schoolSettings['school_name'],
            '{support_email}' => $schoolSettings['school_email'] ?? '',
            '{support_contact}' => $schoolSettings['school_phone'] ?? '',
            '{class}' => $class_name
            // Add more placeholders as needed
        ];

        // Replace the placeholders in the template content
        foreach ($placeholders as $placeholder => $replacement) {
            $templateContent = str_replace($placeholder, $replacement, $templateContent);
        }

        return $templateContent;
    }

    // School fees receipt pdf
    /*
    * @param $feesPaidId
    * @param $studentId
    * @param $type ['panel', 'api']
    */
    public function generateSchoolFeesReceiptPDF($feesId, $studentId, $type = 'panel')
    {
        try {
            $student = $this->student->builder()->where('user_id', $studentId)->firstOrFail();
            $feesPaid = $this->feesPaid->builder()->where('student_id', $studentId)->where('fees_id', $feesId)
                ->with([
                    'fees.fees_class_type.fees_type',
                    'fees.session_year',
                    'compulsory_fee.installment_fee:id,name',
                    'compulsory_fee.fees_paid.fees',
                    'optional_fee' => function ($q) {
                        $q->with([
                            'fees_class_type' => function ($q) {
                                $q->select('id', 'fees_type_id')->with('fees_type:id,name');
                            }
                        ]);
                    },
                    'compulsory_fee' => function ($q) {
                        $q->whereNull('deleted_at')
                            ->with([
                                'installment_fee:id,name',
                                'fees_paid.fees'
                            ]);
                    },
                ])->firstOrFail();

            if ($student->session_year_id == $feesPaid->fees->session_year_id) {
                // current session year
                $student = $this->student->builder()->with('user:id,first_name,last_name', 'class_section.class.stream', 'class_section.class.shift', 'class_section.section', 'class_section.medium')->whereHas('user', function ($q) use ($feesPaid) {
                    $q->where('id', $feesPaid->student_id);
                })->firstOrFail();
            } else {
                // previous session year
                $student = $this->student->builder()->withoutGlobalScopes()->with('user:id,first_name,last_name')->whereHas('user', function ($q) use ($feesPaid) {
                    $q->where('id', $feesPaid->student_id);
                })
                    ->with([
                        'promote_student_history' => function ($q) use ($feesPaid) {
                            $q->where('session_year_id', $feesPaid->fees->session_year_id)
                                ->with('class_section.class.stream', 'class_section.class.shift', 'class_section.section', 'class_section.medium');
                        }
                    ])
                    ->whereHas('promote_student_history', function ($q) use ($feesPaid) {
                        $q->where('session_year_id', $feesPaid->fees->session_year_id);
                    })
                    ->firstOrFail();
            }

            if ($student && $student->relationLoaded('promote_student_history') && $student->promote_student_history) {
                $student->class_section = $student->promote_student_history->class_section;
            }

            $school = $this->cache->getSchoolSettings();

            $data = explode("storage/", $school['horizontal_logo'] ?? '');
            $school['horizontal_logo'] = end($data);

            if ($school['horizontal_logo'] == null) {
                $systemSettings = $this->cache->getSystemSettings();
                $data = explode("storage/", $systemSettings['horizontal_logo'] ?? '');
                $school['horizontal_logo'] = end($data);
            }

            $pdf = Pdf::loadView('fees.fees_receipt', compact('school', 'feesPaid', 'student'));

            if ($type == 'panel') {
                return $pdf->stream('fees-receipt.pdf');
            }
            $output = $pdf->output();
            return array(
                'error' => false,
                'pdf' => base64_encode($output),
            );
        } catch (\Throwable $th) {
            return array(
                'error' => true,
                'message' => $th->getMessage(),
            );
        }
    }
}
