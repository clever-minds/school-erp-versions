<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SessionYear;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SessionYearApiController extends Controller
{
    public function index()
    {
        try {
            $sessionYears = SessionYear::where('school_id', Auth::user()->school_id)->get();
            return ResponseService::successResponse('Data Fetched Successfully.', $sessionYears);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "SessionYearApiController -> index");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $data = $request->all();
            $data['school_id'] = Auth::user()->school_id;
            
            if (isset($data['default']) && $data['default'] == 1) {
                SessionYear::where('school_id', Auth::user()->school_id)->update(['default' => 0]);
            }

            SessionYear::create($data);
            return ResponseService::successResponse('Session Year created successfully.');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "SessionYearApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $sessionYear = SessionYear::where('id', $id)->where('school_id', Auth::user()->school_id)->first();
            if (!$sessionYear) {
                return ResponseService::errorResponse('Session Year not found');
            }

            $data = $request->all();
            if (isset($data['default']) && $data['default'] == 1) {
                SessionYear::where('school_id', Auth::user()->school_id)->update(['default' => 0]);
            }

            $sessionYear->update($data);
            return ResponseService::successResponse('Session Year updated successfully.');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "SessionYearApiController -> update");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        try {
            $sessionYear = SessionYear::where('id', $id)->where('school_id', Auth::user()->school_id)->first();
            if (!$sessionYear) {
                return ResponseService::errorResponse('Session Year not found');
            }

            if ($sessionYear->default == 1) {
                 return ResponseService::errorResponse('Cannot delete default session year');
            }

            $sessionYear->delete();
            return ResponseService::successResponse('Session Year deleted successfully.');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "SessionYearApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }
}
