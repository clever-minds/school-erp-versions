<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\User\UserInterface;
use App\Repositories\Staff\StaffInterface;
use App\Repositories\StaffSalary\StaffSalaryInterface;
use App\Repositories\ExtraFormField\ExtraFormFieldsInterface;
use App\Services\ResponseService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class StaffTeacherApiController extends Controller
{
    private UserInterface $user;
    private StaffInterface $staff;
    private StaffSalaryInterface $staffSalary;
    private ExtraFormFieldsInterface $extraFormFields;

    public function __construct(UserInterface $user, StaffInterface $staff, StaffSalaryInterface $staffSalary, ExtraFormFieldsInterface $extraFormFields)
    {
        $this->user = $user;
        $this->staff = $staff;
        $this->staffSalary = $staffSalary;
        $this->extraFormFields = $extraFormFields;
    }

    public function teacherList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('teacher-list');
        
        $search = $request->search;
        $showDeleted = $request->show_deactive;

        $sql = $this->user->builder()->role('Teacher')->with('staff', 'staff.staffSalary', 'extra_student_details.form_field')
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orWhere('first_name', 'LIKE', "%$search%")
                        ->orWhere('last_name', 'LIKE', "%$search%")
                        ->orWhere('email', 'LIKE', "%$search%")
                        ->orWhere('mobile', 'LIKE', "%$search%");
                });
            })
            ->when(!empty($showDeleted), function ($query) {
                $query->where('status', 0)->onlyTrashed();
            });

        $teachers = $sql->get();
        return ResponseService::successResponse('Teacher list retrieved successfully', $teachers);
    }

    public function teacherStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('teacher-create');

        $request->validate([
            'first_name'        => 'required',
            'last_name'         => 'required',
            'gender'            => 'required',
            'email'             => 'required|email|unique:users,email',
            'mobile'            => 'required|numeric',
            'dob'               => 'required',
            'qualification'     => 'required',
            'current_address'   => 'required',
            'permanent_address' => 'required',
            'status'            => 'nullable|in:0,1',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,svg,gif,webp',
        ]);

        try {
            DB::beginTransaction();

            $userService = app(UserService::class);
            $user = $userService->createOrUpdateUser($request->except('image'), $request->image);
            $user->assignRole('Teacher');

            // Store Extra Details
            if (isset($request->extra_fields) && is_array($request->extra_fields)) {
                $extraDetails = [];
                foreach ($request->extra_fields as $fields) {
                    $data = null;
                    if (isset($fields['data'])) {
                        $data = (is_array($fields['data']) ? json_encode($fields['data'], JSON_THROW_ON_ERROR) : $fields['data']);
                    }
                    $extraDetails[] = [
                        'user_id'       => $user->id,
                        'form_field_id' => $fields['form_field_id'],
                        'data'          => $data,
                    ];
                }
                if (!empty($extraDetails)) {
                    $this->extraFormFields->createBulk($extraDetails);
                }
            }

            $staff = $this->staff->create([
                'user_id'       => $user->id,
                'qualification' => $request->qualification,
                'salary'        => $request->salary,
                'joining_date'  => date('Y-m-d', strtotime($request->joining_date ?? now()))
            ]);

            // Allowances & Deductions
            foreach (['allowance', 'deduction'] as $type) {
                if ($request->has($type) && is_array($request->$type)) {
                    $salaryData = [];
                    foreach ($request->$type as $item) {
                        if (!empty($item['id'])) {
                            $salaryData[] = [
                                'staff_id' => $staff->id,
                                'payroll_setting_id' => $item['id'],
                                'amount' => $item['amount'] ?? null,
                                'percentage' => $item['percentage'] ?? null
                            ];
                        }
                    }
                    if (!empty($salaryData)) {
                        $this->staffSalary->upsert($salaryData, ['staff_id', 'payroll_setting_id'], ['amount', 'percentage']);
                    }
                }
            }

            DB::commit();
            return ResponseService::successResponse('Teacher created successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "StaffTeacherApiController -> teacherStore");
            return ResponseService::errorResponse('An error occurred while creating the teacher');
        }
    }

    public function teacherUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('teacher-edit');

        $request->validate([
            'first_name'        => 'required',
            'last_name'         => 'required',
            'gender'            => 'required',
            'email'             => 'required|email|unique:users,email,' . $id,
            'mobile'            => 'required|numeric',
            'dob'               => 'required',
            'qualification'     => 'required',
            'current_address'   => 'required',
            'permanent_address' => 'required',
            'status'            => 'nullable|in:0,1',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,svg,gif,webp',
        ]);

        try {
            DB::beginTransaction();

            $userService = app(UserService::class);
            $user = $this->user->findById($id);
            $user = $userService->createOrUpdateUser($request->except('image'), $request->image, $id);

            // Update Extra Details
            if (isset($request->extra_fields) && is_array($request->extra_fields)) {
                foreach ($request->extra_fields as $fields) {
                    $data = null;
                    if (isset($fields['data'])) {
                        $data = (is_array($fields['data']) ? json_encode($fields['data'], JSON_THROW_ON_ERROR) : $fields['data']);
                    }
                    $this->extraFormFields->updateOrCreate(
                        ['user_id' => $user->id, 'form_field_id' => $fields['form_field_id']],
                        ['data' => $data]
                    );
                }
            }

            $staff = $this->staff->builder()->where('user_id', $id)->first();
            if ($staff) {
                $staff->update([
                    'qualification' => $request->qualification,
                    'salary'        => $request->salary,
                    'joining_date'  => date('Y-m-d', strtotime($request->joining_date ?? now()))
                ]);

                // Allowances & Deductions
                foreach (['allowance', 'deduction'] as $type) {
                    if ($request->has($type) && is_array($request->$type)) {
                        $salaryData = [];
                        foreach ($request->$type as $item) {
                            if (!empty($item['id'])) {
                                $salaryData[] = [
                                    'staff_id' => $staff->id,
                                    'payroll_setting_id' => $item['id'],
                                    'amount' => $item['amount'] ?? null,
                                    'percentage' => $item['percentage'] ?? null
                                ];
                            }
                        }
                        if (!empty($salaryData)) {
                            $this->staffSalary->upsert($salaryData, ['staff_id', 'payroll_setting_id'], ['amount', 'percentage']);
                        }
                    }
                }
            }

            DB::commit();
            return ResponseService::successResponse('Teacher updated successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "StaffTeacherApiController -> teacherUpdate");
            return ResponseService::errorResponse('An error occurred while updating the teacher');
        }
    }

    public function teacherDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('teacher-delete');

        try {
            $user = $this->user->builder()->where('id', $id)->first();
            if ($user && $user->hasRole('Teacher')) {
                $user->delete();
                return ResponseService::successResponse('Teacher deleted successfully');
            }
            return ResponseService::errorResponse('Teacher not found');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffTeacherApiController -> teacherDestroy");
            return ResponseService::errorResponse('An error occurred while deleting the teacher');
        }
    }
}
