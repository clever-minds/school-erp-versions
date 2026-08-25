<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Repositories\Holiday\HolidayInterface;
use App\Repositories\Student\StudentInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Throwable;

class ApiController extends Controller {
    private CachingService $cache;
    private HolidayInterface $holiday;
    private StudentInterface $student;

    public function __construct(CachingService $cache, HolidayInterface $holiday, StudentInterface $student) {
        $this->cache = $cache;
        $this->holiday = $holiday;
        $this->student = $student;
    }

    public function logout(Request $request) {
        try {
            $user = $request->user();
            $user->fcm_id = '';
            $user->save();
            $user->currentAccessToken()->delete();
            ResponseService::successResponse('Logout Successfully done');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getHolidays(Request $request) {
        try {
            if($request->child_id){
                $child = $this->student->findById($request->child_id);
                $data = $this->holiday->builder()->where('school_id',$child->user->school_id)->get();
            }else{
                $data = $this->holiday->all();
            }
            ResponseService::successResponse("Holidays Fetched Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }



    /**
     * TODO -
     * Get Data From Getter Attribute so remove htmlspecialchars_decode function
     */
    public function getSettings(Request $request) {
        $request->validate([
            'type' => 'required|in:privacy_policy,contact_us,about_us,terms_condition,app_settings,fees_settings'
        ]);
        try {
            $systemSettings = $this->cache->getSystemSettings();
            if ($request->type == "app_settings") {
                $data = array(
                    'app_link'                   => $systemSettings['app_link'] ?? "",
                    'ios_app_link'               => $systemSettings['ios_app_link'] ?? "",
                    'app_version'                => $systemSettings['app_version'] ?? "",
                    'ios_app_version'            => $systemSettings['ios_app_version'] ?? "",
                    'force_app_update'           => $systemSettings['force_app_update'] ?? "",
                    'app_maintenance'            => $systemSettings['app_maintenance'] ?? "",
                    'teacher_app_link'           => $systemSettings['teacher_app_link'] ?? "",
                    'teacher_ios_app_link'       => $systemSettings['teacher_ios_app_link'] ?? "",
                    'teacher_app_version'        => $systemSettings['teacher_app_version'] ?? "",
                    'teacher_ios_app_version'    => $systemSettings['teacher_ios_app_version'] ?? "",
                    'teacher_force_app_update'   => $systemSettings['teacher_force_app_update'] ?? "",
                    'teacher_app_maintenance'    => $systemSettings['teacher_app_maintenance'] ?? "",
                    'tagline'                    => $systemSettings['tag_line'] ?? "",
                );

            } else {
                $data = isset($systemSettings[$request->type]) ? htmlspecialchars_decode($systemSettings[$request->type]) : "";
            }
            ResponseService::successResponse("Data Fetched Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    protected function forgotPassword(Request $request) {
        $request->validate([
            'email' => "required|email"
        ]);
        try {
            $response = Password::sendResetLink($request->email);
            if ($response == Password::RESET_LINK_SENT) {
                ResponseService::successResponse("Forgot Password email send successfully");
            } else {
                ResponseService::errorResponse("Cannot send Reset Password Link.Try again later", null, config('constants.RESPONSE_CODE.RESET_PASSWORD_FAILED'));
            }
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    protected function changePassword(Request $request) {
        $request->validate([
            'current_password'     => 'required',
            'new_password'         => 'required|min:6',
            'new_confirm_password' => 'same:new_password',
        ]);

        try {
            $user = $request->user();
            if (Hash::check($request->current_password, $user->password)) {
                $user->update(['password' => Hash::make($request->new_password)]);
                ResponseService::successResponse("Password Changed successfully.");
            } else {
                ResponseService::errorResponse("Invalid Password", null, config('constants.RESPONSE_CODE.INVALID_PASSWORD'));
            }
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }
}
