<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use App\Models\User;
use App\Models\Student;
use App\Models\Guardian;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Student\StudentInterface;
use App\Repositories\User\UserInterface;
use App\Repositories\Subscription\SubscriptionInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Services\SubscriptionService;
use App\Services\CachingService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Throwable;
use Illuminate\Support\Facades\Auth;

class StaffManageApiController extends Controller
{
    private StudentInterface $student;
    private UserInterface $user;
    private SubscriptionInterface $subscription;
    private SubscriptionService $subscriptionService;
    private CachingService $cache;
    private SessionYearInterface $sessionYear;

    public function __construct(
        StudentInterface $student, 
        UserInterface $user,
        SubscriptionInterface $subscription,
        SubscriptionService $subscriptionService,
        CachingService $cache,
        SessionYearInterface $sessionYear
    ) {
        $this->student = $student;
        $this->user = $user;
        $this->subscription = $subscription;
        $this->subscriptionService = $subscriptionService;
        $this->cache = $cache;
        $this->sessionYear = $sessionYear;
    }

    // --- MANAGE STUDENT APIs ---

    public function studentShow(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('student-list');
        
        try {
            $student = $this->student->builder()
                ->where('user_id', $id)
                ->with('user.extra_student_details.form_field', 'guardian', 'class.medium', 'class.stream')
                ->first();

            if (!$student) {
                return ResponseService::errorResponse('Student not found');
            }

            $tempRow = $student->toArray();
            $tempRow['extra_fields'] = $student->user->extra_student_details;
            
            foreach ($student->user->extra_student_details as $key => $field) {
                $data = '';
                if ($field->form_field->type == 'checkbox') {
                    $data = json_decode($field->data);
                } else if ($field->form_field->type == 'file') {
                    $data = Storage::url($field->data);
                } else if ($field->form_field->type == 'dropdown') {
                    $data = $field->data ?? $field->form_field->default_values;
                } else {
                    $data = $field->data;
                }
                $tempRow[$field->form_field->name] = $data;
            }

            return ResponseService::successResponse('Student details fetched successfully', $tempRow);
        } catch (\Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffManageApiController ---> studentShow');
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('student-delete');
        
        try {
            DB::beginTransaction();

            $student = $this->student->builder()->with('guardian')->where('user_id', $id)->first();

            if ($student && $student->guardian) {
                $guardianStudentCount = $this->student->builder()->where('guardian_id', $student->guardian_id)->count();

                if ($guardianStudentCount == 1) {
                    $this->user->builder()->where('id', $student->guardian->id)->withTrashed()->forceDelete();
                }
            }

            $this->student->builder()->where('user_id', $id)->withTrashed()->forceDelete();
            $this->user->builder()->where('id', $id)->withTrashed()->forceDelete();

            DB::commit();
            return ResponseService::successResponse("Student deleted permanently");
        } catch (\Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffManageApiController ---> destroy", 'cannot_delete_because_data_is_associated_with_other_data');
            return ResponseService::errorResponse('Cannot delete because data is associated with other data');
        }
    }

    public function studentList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('student-list');
        
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);
        $search = $request->get('search');
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        
        $query = $this->student->builder()->with('user', 'guardian', 'class_section.class', 'class_section.section');
        
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%$search%")
                  ->orWhere('last_name', 'LIKE', "%$search%")
                  ->orWhere('email', 'LIKE', "%$search%");
            });
        }
        
        if ($classId) {
            $query->whereHas('class_section', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }
        
        if ($sectionId) {
            $query->whereHas('class_section', function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId);
            });
        }

        $total = $query->count();
        $students = $query->skip($offset)->take($limit)->get();
        
        $data = [
            'total' => $total,
            'rows' => $students
        ];
        
        return ResponseService::successResponse('Student list fetched successfully', $data);
    }

    public function studentStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('student-create');
        
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'required|in:male,female',
            'mobile' => 'nullable|digits:10',
            'image' => 'nullable|mimes:jpeg,png,jpg,svg|image|max:2048',
            'dob' => 'required',
            'class_section_id' => 'required|numeric',
            'admission_no' => 'required|unique:users,email',
            'admission_date' => 'required',
            'session_year_id' => 'required|numeric',
            'guardian_email' => 'nullable|email',
            'guardian_first_name' => 'required|string',
            'guardian_last_name' => 'required|string',
            'guardian_mobile' => 'required|digits:10',
            'student_mother_name' => 'required',
            'guardian_gender' => 'required|in:male,female',
            'guardian_image' => 'nullable|mimes:jpg,jpeg,png|max:4096',
            'status' => 'nullable|in:0,1',
            'rte_status' => 'nullable|in:RTE,NON_RTE',
            'cast' => 'required|in:GENERAL,OBC,SC,ST,EWS',
            'nationality' => 'required',
            'last_school' => 'required',
            'last_cleared_class' => 'required',
            'education_board' => 'required',
            'remarks' => 'required',
            'birth_place' => 'required',
            'blood_group' => 'required',
            'pen_no' => 'required|digits:11',
            'campus' => 'nullable',
        ]);

        try {
            DB::beginTransaction();

            $today_date = Carbon::now()->format('Y-m-d');
            $subscription = $this->subscription->builder()->doesntHave('subscription_bill')->whereDate('start_date', '<=', $today_date)->where('end_date', '>=', $today_date)->whereHas('package', function ($q) {
                $q->where('is_trial', 1);
            })->first();

            if ($subscription) {
                $systemSettings = $this->cache->getSystemSettings();
                $student = $this->user->builder()->role('Student')->withTrashed()->count();
                if ($student >= $systemSettings['student_limit']) {
                    $message = "The free trial allows only " . $systemSettings['student_limit'] . " students.";
                    ResponseService::errorResponse($message);
                }
            } else {
                $subscription = $this->subscriptionService->active_subscription(Auth::user()->school_id);
                if ($subscription && $subscription->package_type == 0) {
                    $status = $this->subscriptionService->check_user_limit($subscription, "Students");
                    if (!$status) {
                        ResponseService::errorResponse('You reach out limits');
                    }
                }
            }

            $guardianUser = $this->user->builder()->whereHas('roles', function ($q) {
                $q->where('name', '!=', 'Guardian');
            })->where(function($q) use ($request) {
                if (!empty($request->guardian_email)) {
                    $q->where('email', $request->guardian_email);
                }
                $q->orWhere('mobile', $request->guardian_mobile);
            })->withTrashed()->first();
            
            if ($guardianUser) {
                ResponseService::errorResponse("Mobile Number or Email ID is already taken for Other Role");
            }
            
            $userService = app(UserService::class);
            $sessionYear = $this->sessionYear->findById($request->session_year_id);
            $guardian = $userService->createOrUpdateParent($request->guardian_first_name, $request->guardian_last_name, $request->student_mother_name, $request->guardian_email, $request->guardian_mobile, $request->guardian_gender, $request->guardian_image, null, $request->guardian_id);
            $is_send_notification = false;
            $middle_name = $request->middle_name ?? $request->guardian_first_name;
            $user = $userService->createStudentUser($request->first_name, $middle_name, $request->last_name, $request->admission_no, $request->mobile, $request->dob, $request->gender, $request->image, $request->class_section_id, $request->admission_date, $request->current_address, $request->permanent_address, $sessionYear->id, $guardian->id, $request->extra_fields ?? [], $request->status ?? 0, $is_send_notification, $request->rte_status, $request->cast, $request->nationality, $request->birth_place, $request->blood_group, $request->last_school, $request->last_cleared_class, $request->education_board, $request->remarks, $request->pen_no, $request->campus);

            DB::commit();
            return ResponseService::successResponse('Student admitted successfully', $user);
        } catch (Throwable $e) {
            if ($e instanceof TypeError && Str::contains($e->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager'])) {
                DB::commit();
                return ResponseService::successResponse("Student Registered successfully. But Email not sent.");
            } else {
                DB::rollBack();
                ResponseService::logErrorResponse($e, "StaffManageApiController -> studentStore method");
                return ResponseService::errorResponse('An error occurred during registration.');
            }
        }
    }



    public function studentUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('student-edit');
        
        $rules = [
            'first_name' => 'required',
            'middle_name' => 'nullable',
            'last_name' => 'required',
            'gender' => 'required|in:male,female',
            'admission_no' => 'required|unique:users,email,' . $id,
            'mobile' => 'nullable|digits:10',
            'image' => 'nullable|mimes:jpeg,png,jpg,svg|image|max:2048',
            'dob' => 'required',
            'session_year_id' => 'required|numeric',
            'rte_status' => 'nullable|in:RTE,NON_RTE',
            'cast' => 'required|in:GENERAL,OBC,SC,ST,EWS',
            'nationality' => 'required',
            'last_school' => 'required',
            'last_cleared_class' => 'required',
            'education_board' => 'required',
            'remarks' => 'required',
            'birth_place' => 'required',
            'blood_group' => 'required',
            'pen_no' => 'required|digits:11',
            'campus' => 'nullable',
        ];
        $request->validate($rules);

        try {
            DB::beginTransaction();
            $userService = app(UserService::class);
            $sessionYear = $this->sessionYear->findById($request->session_year_id);
            $guardian = $userService->createOrUpdateParent($request->guardian_first_name, $request->guardian_last_name, $request->student_mother_name, $request->guardian_email, $request->guardian_mobile, $request->guardian_gender, $request->guardian_image, $request->parent_reset_password, $request->guardian_id);
            $middle_name = $request->middle_name ?? $request->guardian_first_name;
            $userService->updateStudentUser($id, $request->first_name, $middle_name, $request->last_name, $request->mobile, $request->dob, $request->gender, $request->image, $sessionYear->id, $request->extra_fields ?? [], $guardian->id, $request->current_address, $request->permanent_address, $request->reset_password, $request->class_section_id, $request->admission_no, $request->rte_status, $request->cast, $request->nationality, $request->birth_place, $request->blood_group, $request->last_school, $request->last_cleared_class, $request->education_board, $request->remarks, $request->pen_no, $request->campus);
            
            DB::commit();
            return ResponseService::successResponse('Student details updated successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffManageApiController -> studentUpdate method");
            return ResponseService::errorResponse('An error occurred during update.');
        }
    }

    public function changeStudentStatus(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('student-edit');
        
        $request->validate([
            'status' => 'required|in:0,1'
        ]);

        try {
            DB::beginTransaction();
            $user = $this->user->findTrashedById($id);
            if (!$user) {
                ResponseService::errorResponse('Student not found');
            }
            
            // If user is being activated
            if ($request->status == 1 && $user->status == 0) {
                $subscription = $this->subscriptionService->active_subscription(Auth::user()->school_id);
                // If prepaid plan check student limit
                if ($subscription && $subscription->package_type == 0) {
                    $status = $this->subscriptionService->check_user_limit($subscription, "Students");
                    if (!$status) {
                        ResponseService::errorResponse('You reach out limits');
                    }
                }
            }

            $this->user->builder()->where('id', $id)->withTrashed()->update([
                'status' => $request->status, 
                'deleted_at' => $request->status == 0 ? now() : null
            ]);
            
            DB::commit();
            $statusMessage = $request->status == 1 ? 'Student activated successfully' : 'Student inactivated successfully';
            return ResponseService::successResponse($statusMessage);
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'StaffManageApiController ---> changeStudentStatus');
            ResponseService::errorResponse();
        }
    }

    public function admissionInquiries(Request $request)
    {
        ResponseService::noPermissionThenSendJson('student-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

        $sql = $this->student->builder()->where('application_type', 'online')->where('application_status', 0)->with('user.extra_student_details.form_field', 'guardian', 'class.medium', 'class.stream')
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('user_id', 'LIKE', "%$search%")
                            ->orWhere('class_section_id', 'LIKE', "%$search%")
                            ->orWhere('admission_no', 'LIKE', "%$search%")
                            ->orWhere('roll_number', 'LIKE', "%$search%")
                            ->orWhere('admission_date', 'LIKE', date('Y-m-d', strtotime("%$search%")))
                            ->orWhereHas('user', function ($q) use ($search) {
                                $q->where('first_name', 'LIKE', "%$search%")
                                    ->orwhere('last_name', 'LIKE', "%$search%")
                                    ->orwhere('email', 'LIKE', "%$search%")
                                    ->orwhere('dob', 'LIKE', "%$search%")
                                    ->orWhereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'");
                            })->orWhereHas('guardian', function ($q) use ($search) {
                                $q->where('first_name', 'LIKE', "%$search%")
                                    ->orwhere('last_name', 'LIKE', "%$search%")
                                    ->orwhere('email', 'LIKE', "%$search%")
                                    ->orwhere('dob', 'LIKE', "%$search%")
                                    ->orWhereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'");
                            });
                    });
                })->whereHas('user', function ($q) {
                    $q->where('status', 0);
                });
            })
            ->when(request('class_id') != null, function ($query) {
                $classId = request('class_id');
                $query->where('class_id', $classId);
            })
            ->when(request('campus') != null, function ($query) {
                $campus = request('campus');
                $query->where('campus', $campus);
            });

        $total = $sql->count();
        if (!empty($request->class_id)) {
            $sql = $sql->orderBy('roll_number', 'ASC');
        } else {
            $sql = $sql->orderBy($sort, $order);
        }
        $sql->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['extra_fields'] = $row->user->extra_student_details;
            
            foreach ($row->user->extra_student_details as $key => $field) {
                $data = '';
                if ($field->form_field->type == 'checkbox') {
                    $data = json_decode($field->data);
                } else if ($field->form_field->type == 'file') {
                    $data = Storage::url($field->data);
                } else if ($field->form_field->type == 'dropdown') {
                    $data = $field->data ?? $field->form_field->default_values;
                } else {
                    $data = $field->data;
                }
                $tempRow[$field->form_field->name] = $data;
            }
            $rows[] = $tempRow;
        }

        $bulkData['data'] = $rows;
        return ResponseService::successResponse('Admission inquiries fetched successfully', $bulkData);
    }

    public function updateApplicationStatus(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('student-create');

        $request->validate([
            'application_status' => 'required|in:1,2',
            'first_name' => 'required',
            'last_name' => 'required',
            'dob' => 'required',
            'gender' => 'required|in:male,female',
            'admission_no' => 'required',
            'class_section_id' => 'required_if:application_status,1',
            'campus' => 'nullable',
        ]);

        try {
            $userService = app(UserService::class);
            DB::beginTransaction();

            $user = $this->user->findTrashedById($id);
            if (!$user) {
                return ResponseService::errorResponse('Inquiry not found');
            }
            $student = $this->student->builder()->where('user_id', $id)->first();
            
            // Update Guardian if details are provided
            $guardianID = $student->guardian_id;
            if ($request->guardian_first_name || $request->guardian_mobile) {
                $guardian = $userService->createOrUpdateParent(
                    $request->guardian_first_name,
                    $request->guardian_last_name,
                    $request->student_mother_name ?? ($student->guardian->mother_name ?? null),
                    $request->guardian_email,
                    $request->guardian_mobile,
                    $request->guardian_gender,
                    $request->file('guardian_image'),
                    $request->parent_reset_password
                );
                $guardianID = $guardian->id;
            }

            // Update Student and User Details
            $userService->updateStudentUser(
                $id,
                $request->first_name,
                $request->middle_name ?? $user->middle_name,
                $request->last_name,
                $request->mobile ?? $user->mobile,
                $request->dob,
                $request->gender,
                $request->file('image'),
                $request->session_year_id ?? $student->session_year_id,
                $request->extra_fields ?? [],
                $guardianID,
                $request->current_address ?? $user->current_address,
                $request->permanent_address ?? $user->permanent_address,
                $request->reset_password,
                $request->class_section_id ?? $student->class_section_id,
                $request->admission_no,
                $student->rte_status,
                $student->cast,
                $student->nationality,
                $student->birth_place,
                $student->blood_group,
                $student->last_school,
                $student->last_cleared_class,
                $student->education_board,
                $student->remarks,
                $student->pen_no,
                $request->campus
            );

            $user->refresh();
            $student->refresh();

            if ($user->status == 0 && $request->application_status == 1) {
                $subscription = $this->subscriptionService->active_subscription(Auth::user()->school_id);
                if ($subscription && $subscription->package_type == 0) {
                    $status = $this->subscriptionService->check_user_limit($subscription, "Students");
                    if (!$status) {
                        return ResponseService::errorResponse('You reach out limits');
                    }
                }
            }

            if ($request->application_status == 1) {
                $this->student->builder()->where('user_id', $id)->withTrashed()->update(['application_status' => 1, 'class_section_id' => $request->class_section_id]);
                $password = str_replace('-', '', date('d-m-Y', strtotime($user->dob)));
                $guardian = $this->user->guardian()->withTrashed()->where('id', $student->guardian_id)->firstOrFail();
                $userService->sendRegistrationEmail($guardian, $user, $student->admission_no, $password);
                $message = "Application accepted successfully";
            } else {
                $this->student->builder()->where('user_id', $id)->withTrashed()->update(['application_status' => 2]);
                $guardian = $this->user->guardian()->withTrashed()->where('id', $student->guardian_id)->firstOrFail();
                $userService->sendApplicationRejectEmail($user, $student, $guardian);
                $user->delete();
                $message = "Application rejected successfully";
            }

            DB::commit();
            return ResponseService::successResponse($message);
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'StaffManageApiController ---> updateApplicationStatus');
            return ResponseService::errorResponse();
        }
    }

    public function updateUniNo(Request $request)
    {
        ResponseService::noPermissionThenSendJson('student-edit');
        
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'student_uni' => 'required|string|max:255',
            'student_pen_no' => 'nullable|string|max:11',
        ], [
            'student_id.required' => 'Student ID is required.',
            'student_uni.required' => 'Student UDI Number is required.',
        ]);

        try {
            DB::beginTransaction();

            $student = $this->student->builder()->where('id', $request->student_id)->firstOrFail();
            $student->update([
                'uni_no' => $request->student_uni,
                'pen_no' => $request->student_pen_no,
            ]);

            DB::commit();
            return ResponseService::successResponse('Student UNI Number updated successfully!');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'StaffManageApiController ---> updateUniNo');
            return ResponseService::errorResponse();
        }
    }

    public function updateProfile(Request $request)
    {
        ResponseService::noPermissionThenSendJson('student-edit');
        
        try {
            $data = array();
            if ($request->student_image) {
                foreach ($request->student_image as $key => $profile) {
                    $data[] = [
                        'id' => $key,
                        'image' => $profile
                    ];
                }
            }
            if ($request->guardian_image) {
                foreach ($request->guardian_image as $key => $profile) {
                    $data[] = [
                        'id' => $key,
                        'image' => $profile
                    ];
                }
            }
            
            if (!empty($data)) {
                $this->user->upsertProfile($data, ['id'], ['image']);
            }
            
            return ResponseService::successResponse('Student profile updated successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffManageApiController ---> updateProfile');
            return ResponseService::errorResponse();
        }
    }

    public function searchStudent(Request $request)
    {
        ResponseService::noPermissionThenSendJson('student-list');
        try {
            $search = $request->input('search');

            $students = DB::table('users')
                ->join('students', 'students.user_id', '=', 'users.id')
                ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', 'student')
                ->when($search, function ($query, $search) {
                    $query->where('students.admission_no', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.first_name', 'like', "%{$search}%")
                        ->orWhere('users.last_name', 'like', "%{$search}%");
                })
                ->select(
                    'users.id as user_id',
                    'students.id as student_id',
                    'students.uni_no as uni_no',
                    'students.pen_no as pen_no',
                    'students.admission_no as gr_no',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                    'users.mobile'
                )
                ->orderBy('students.admission_no', 'asc')
                ->limit(20)
                ->get();

            $formatted = $students->map(function ($student) {
                return [
                    'id' => $student->student_id,
                    'text' => $student->gr_no . ' - ' . $student->first_name . ' ' . $student->last_name,
                    'email' => $student->email,
                    'uni_no' => $student->uni_no,
                    'pen_no' => $student->pen_no,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'mobile' => $student->mobile,
                ];
            });

            return ResponseService::successResponse('Students fetched successfully', [
                'data' => $formatted,
                'total_count' => $formatted->count(),
            ]);
        } catch (\Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffManageApiController ---> searchStudent');
            return ResponseService::errorResponse();
        }
    }

    // --- MANAGE GUARDIAN APIs ---

    public function guardianList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('guardian-list');
        
        try {
            $offset = request('offset', 0);
            $limit = request('limit', 10);
            $sort = request('sort', 'id');
            $order = request('order', 'DESC');

            $sql = $this->user->guardian()->with('child.class_section');

            if ($request->class_id && $request->class_id != 'all') {
                $sql->whereHas('child.class_section', function ($q) use ($request) {
                    $q->where('class_id', $request->class_id);
                });
            }

            if ($request->class_section_id && $request->class_section_id != 'all') {
                $sql->whereHas('child', function ($q) use ($request) {
                    $q->where('class_section_id', $request->class_section_id);
                });
            }
        
            $sql = $sql->owner();

            if (!empty($request->search)) {
                $search = $request->search;
                $sql->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")->orwhere('first_name', 'LIKE', "%$search%")
                        ->orwhere('last_name', 'LIKE', "%$search%")->orwhere('gender', 'LIKE', "%$search%")
                        ->orwhere('email', 'LIKE', "%$search%")->orwhere('mobile', 'LIKE', "%$search%");
                });
            }
            $total = $sql->count();

            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();
            $no = 1;
            foreach ($res as $row) {
                $tempRow = $row->toArray();
                $tempRow['no'] = $no++;
                $rows[] = $tempRow;
            }

            $bulkData['data'] = $rows;
            return ResponseService::successResponse('Guardian list fetched successfully', $bulkData);
        } catch (\Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffManageApiController ---> guardianList');
            return ResponseService::errorResponse();
        }
    }

    public function guardianStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('guardian-create');
        // Logic for creating guardian goes here
        return ResponseService::successResponse('Guardian created successfully');
    }

    public function guardianUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('guardian-edit');
        
        $request->validate([
            'first_name' => 'required',
            'email'      => 'required',
            'last_name'  => 'required',
            'gender'     => 'required',
            'mobile'     => 'required|unique:users,mobile,' . $id,
            'image'      => 'nullable|image|mimes:jpeg,png,jpg,svg,gif,webp',
        ]);

        try {
            $data = $request->except('_token', '_method', 'reset_password');
            $guardian = $this->user->guardian()->where('id', $id)->firstOrFail();

            if ($request->hasFile('image')) {
                if ($guardian->getRawOriginal('image')) {
                    UploadService::delete($guardian->getRawOriginal('image'));
                }
                $data['image'] = UploadService::upload($request->image, 'guardian');
            }

            if ($request->reset_password) {
                $data['password'] = Hash::make($request->mobile);
            }

            $guardian->update($data);
            $guardian->assignRole('Guardian');

            return ResponseService::successResponse('Guardian details updated successfully');
        } catch (\Throwable $e) {
            ResponseService::logErrorResponse($e, 'StaffManageApiController ---> guardianUpdate');
            return ResponseService::errorResponse();
        }
    }

    public function guardianDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('guardian-delete');
        // Logic for deleting guardian goes here
        return ResponseService::successResponse('Guardian deleted successfully');
    }

    // --- RESET PASSWORD API ---

    public function resetPassword(Request $request)
    {
        ResponseService::noPermissionThenSendJson('student-reset-password');
        // Logic for resetting password goes here
        return ResponseService::successResponse('Password reset successfully');
    }
}
