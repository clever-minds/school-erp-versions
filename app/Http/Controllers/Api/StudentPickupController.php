<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\StudentPickup\StudentPickupInterface;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StudentPickupController extends Controller
{
    private StudentPickupInterface $studentPickup;

    public function __construct(StudentPickupInterface $studentPickup)
    {
        $this->studentPickup = $studentPickup;
    }

    /**
     * Parent requests an OTP for student pickup
     */
    public function createPickupRequest(Request $request)
    {
        // Permission check for parent could be 'student-pickup-create'
        // But for now, we'll keep it as is if it's strictly for parents in the mobile app.
        // Usually, mobile app uses different auth guards.

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|numeric',
            'pickup_person_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $user = Auth::user();
            $otp = sprintf("%06d", mt_rand(1, 999999));

            $data = [
                'student_id' => $request->student_id,
                'parent_id' => $user->id,
                'pickup_person_name' => $request->pickup_person_name,
                'otp' => $otp,
                'status' => 0, // Pending
                'school_id' => $user->school_id,
            ];

            $pickupRequest = $this->studentPickup->create($data);

            ResponseService::successResponse('Pickup request created successfully', [
                'otp' => $otp,
                'pickup_person_name' => $request->pickup_person_name,
                'request_id' => $pickupRequest->id
            ]);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /**
     * Get pickup requests for a student (for parent)
     */
    public function getStudentPickupRequests(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $requests = $this->studentPickup->builder()
                ->where('student_id', $request->student_id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            ResponseService::successResponse('Pickup requests fetched successfully', $requests);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /**
     * Staff/Gatekeeper verifies OTP
     */
    public function verifyPickupOTP(Request $request)
    {
        // ResponseService::noPermissionThenSendJson('student-pickup-edit');

        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $pickupRequest = $this->studentPickup->builder()
                ->where('otp', $request->otp)
                ->where('status', 0) // Only pending
                ->where('created_at', '>=', Carbon::now()->subHours(24)) // E.g., valid for 24 hours
                ->first();

            if (!$pickupRequest) {
                ResponseService::errorResponse('Invalid or expired OTP', null);
            }

            $pickupRequest->update([
                'status' => 1, // Verified
                'verified_by' => Auth::user()->id,
                'verified_at' => Carbon::now(),
            ]);

            $pickupRequest->load('student.user', 'student.class_section.class', 'student.class_section.section', 'student.guardian', 'student.session_year', 'student.shift');

            ResponseService::successResponse('OTP verified successfully. You can allow the pickup.', [
                'student_id' => $pickupRequest->student_id,
                'student_name' => $pickupRequest->student->full_name,
                'pickup_person_name' => $pickupRequest->pickup_person_name,
                'student' => $pickupRequest->student,
            ]);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /**
     * List all pickup requests for staff/admin
     */
    public function getAllPickupRequests(Request $request)
    {
        // ResponseService::noPermissionThenSendJson('student-pickup-list');

        try {
            $user = Auth::user();
            $query = $this->studentPickup->builder()->with(['student.user', 'student.class_section.class', 'student.class_section.section', 'parent', 'verifier']);
            
            if (!$user->hasRole('Super Admin')) {
                $query->where('school_id', $user->school_id);
            }

            $requests = $query->orderBy('created_at', 'desc')->paginate(15);

            ResponseService::successResponse('All pickup requests fetched successfully', $requests);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }
}
