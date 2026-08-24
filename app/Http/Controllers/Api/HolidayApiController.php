<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Holiday;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Throwable;

class HolidayApiController extends Controller
{
    public function index()
    {
        $request = request();
        try {
            $holidays = Holiday::where('school_id', Auth::user()->school_id)
                ->when($request->session_year_id, function($q) use($request) {
                    $q->where('session_year_id', $request->session_year_id);
                })->get();
                
            return ResponseService::successResponse('Data Fetched Successfully.', $holidays);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "HolidayApiController -> index");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        if ($request->type == 'holiday') {
            $validator = Validator::make($request->all(), [
                'date' => 'required',
                'title' => 'required'
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'class_ids' => 'required'
            ]);
        }

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $data = $request->all();
            $data['school_id'] = Auth::user()->school_id;

            if ($request->has('class_ids') && is_array($request->class_ids)) {
                $data['class_ids'] = implode(',', $request->class_ids);
            }

            if ($request->type == 'holiday') {
                $dates = explode(' - ', $request->date);
                $data['date'] = Carbon::parse($dates[0])->format('Y-m-d');
                $data['end_date'] = isset($dates[1]) ? Carbon::parse($dates[1])->format('Y-m-d') : Carbon::parse($dates[0])->format('Y-m-d');
            } else {
                $data['date'] = null;
            }

            Holiday::create($data);
            return ResponseService::successResponse('Holiday created successfully.');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "HolidayApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function update(Request $request, $id)
    {
        if ($request->type == 'holiday') {
            $validator = Validator::make($request->all(), [
                'date' => 'required',
                'title' => 'required'
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'class_ids' => 'required'
            ]);
        }

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $holiday = Holiday::where('id', $id)->where('school_id', Auth::user()->school_id)->first();
            if (!$holiday) {
                return ResponseService::errorResponse('Holiday not found');
            }

            $data = $request->all();
            if ($request->has('class_ids') && is_array($request->class_ids)) {
                $data['class_ids'] = implode(',', $request->class_ids);
            }

            if ($request->type == 'holiday') {
                $dates = explode(' - ', $request->date);
                $data['date'] = Carbon::parse($dates[0])->format('Y-m-d');
                $data['end_date'] = isset($dates[1]) ? Carbon::parse($dates[1])->format('Y-m-d') : Carbon::parse($dates[0])->format('Y-m-d');
            } else {
                $data['date'] = null;
            }

            $holiday->update($data);
            return ResponseService::successResponse('Holiday updated successfully.');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "HolidayApiController -> update");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        try {
            $holiday = Holiday::where('id', $id)->where('school_id', Auth::user()->school_id)->first();
            if (!$holiday) {
                return ResponseService::errorResponse('Holiday not found');
            }

            $holiday->delete();
            return ResponseService::successResponse('Holiday deleted successfully.');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "HolidayApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }
}
