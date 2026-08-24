<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Students;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ConsentFormApiController extends Controller
{
    /**
     * List all students with their consent form dates.
     * Permission: consent-form-list
     */
    public function index()
    {
        ResponseService::noPermissionThenSendJson('consent-form-list');

        try {
            $students = Students::select('id', 'user_id', 'class_section_id', 'consent_form_date')
                ->with(['user:id,first_name,last_name,image', 'class_section.class', 'class_section.section'])
                ->get();

            return ResponseService::successResponse('Consent forms fetched successfully', $students);
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse('Something went wrong');
        }
    }


}
