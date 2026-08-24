<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Timetable;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TimetableApiController extends Controller
{
    public function index()
    {
        $request = request();
        try {
            $timetables = Timetable::whereHas('class_subject.class_section', function($q) {
                $q->where('school_id', Auth::user()->school_id);
            })
            ->when($request->class_section_id, function($q) use($request) {
                $q->whereHas('class_subject', function($q2) use($request) {
                    $q2->where('class_section_id', $request->class_section_id);
                });
            })
            ->with(['class_subject.subject', 'teacher.user'])
            ->get();
            return ResponseService::successResponse('Data Fetched Successfully.', $timetables);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "TimetableApiController -> index");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'timetable' => 'required|array',
            'timetable.*.day' => 'required',
            'timetable.*.start_time' => 'required',
            'timetable.*.end_time' => 'required',
            'timetable.*.subject_id' => 'required',
            'timetable.*.teacher_id' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            // Delete old timetable for this class section before saving new one
            $class_section_id = $request->class_section_id;
            
            Timetable::whereHas('class_subject', function($q) use($class_section_id) {
                $q->where('class_section_id', $class_section_id);
            })->delete();

            foreach ($request->timetable as $item) {
                // Find class_subject_id based on class_section_id and subject_id
                $classSubject = \App\Models\ClassSubject::where('class_section_id', $class_section_id)
                    ->where('subject_id', $item['subject_id'])
                    ->first();

                if ($classSubject) {
                    Timetable::create([
                        'class_subject_id' => $classSubject->id,
                        'teacher_id' => $item['teacher_id'],
                        'start_time' => $item['start_time'],
                        'end_time' => $item['end_time'],
                        'day' => $item['day'],
                        'note' => $item['note'] ?? null,
                    ]);
                }
            }

            return ResponseService::successResponse('Timetable created/updated successfully.');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "TimetableApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        try {
            $timetable = Timetable::where('id', $id)->first();
            if (!$timetable) {
                return ResponseService::errorResponse('Timetable not found');
            }

            $timetable->delete();
            return ResponseService::successResponse('Timetable deleted successfully.');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "TimetableApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }
}
