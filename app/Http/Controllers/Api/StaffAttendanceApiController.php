<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Attendance\AttendanceInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\Student\StudentInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class StaffAttendanceApiController extends Controller
{
    private AttendanceInterface $attendance;
    private ClassSectionInterface $classSection;
    private StudentInterface $student;
    private CachingService $cache;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

    public function __construct(
        AttendanceInterface $attendance,
        ClassSectionInterface $classSection,
        StudentInterface $student,
        CachingService $cache,
        SessionYearsTrackingsService $sessionYearsTrackingsService
    ) {
        $this->attendance = $attendance;
        $this->classSection = $classSection;
        $this->student = $student;
        $this->cache = $cache;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
    }

    public function getAttendanceData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric',
            'date'             => 'required|date',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $response = $this->attendance->builder()->select('type')->where(['date' => date('Y-m-d', strtotime($request->date)), 'class_section_id' => $request->class_section_id])->pluck('type')->first();
            return ResponseService::successResponse('Data Fetched successfully', ['type' => $response]);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffAttendanceApiController -> getAttendanceData");
            return ResponseService::errorResponse();
        }
    }

    public function show(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['class-teacher', 'attendance-list']);

        $sort = $request->input('sort', 'roll_number');
        $order = $request->input('order', 'ASC');
        $search = $request->input('search');

        $class_section_id = $request->class_section_id;
        $date = $request->date ? date('Y-m-d', strtotime($request->date)) : '';
        $sessionYear = $this->cache->getDefaultSessionYear();

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric',
            'date'             => 'required|date',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $attendanceData = array();
            $total = 0;

            $attendanceQuery = $this->attendance->builder()->with('user.student')->where(['date' => $date, 'class_section_id' => $class_section_id, 'session_year_id' => $sessionYear->id])->whereHas('user', function ($q) {
                $q->whereNull('deleted_at');
            })->whereHas('user.student', function ($q) use ($sessionYear) {
                $q->where('session_year_id', $sessionYear->id);
            });

            if ($date != '' && $attendanceQuery->count() > 0) {
                $attendanceQuery->when($search, function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")->orWhereHas('user', function ($q) use ($search) {
                        $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'");
                    });
                })->where('date', $date)->whereHas('user.student', function ($q) use ($sessionYear) {
                    $q->where('session_year_id', $sessionYear->id);
                });

                $total = $attendanceQuery->count();
                $attendanceData = $attendanceQuery->get();
            } else if ($class_section_id) {
                $studentQuery = $this->student->builder()->where('session_year_id', $sessionYear->id)->where('class_section_id', $class_section_id)->with('user')
                    ->whereHas('user', function ($q) {
                        $q->whereNull('deleted_at');
                    })
                    ->when($search, function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")->orWhereHas('user', function ($q) use ($search) {
                            $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'")->where('deleted_at', NULL);
                        });
                    })->where('session_year_id', $sessionYear->id)->where('class_section_id', $class_section_id);

                $total = $studentQuery->count();
                $studentQuery->orderBy($sort, $order);
                $attendanceData = $studentQuery->get();
            }

            $rows = [];
            $no = 1;

            foreach ($attendanceData as $row) {
                $type = $row->type ?? NULL;
                $rows[] = [
                    'id'           => count($attendanceData) > 0 && isset($row->type) ? $row->id : null,
                    'no'           => $no,
                    'student_id'   => count($attendanceData) > 0 && isset($row->type) ? $row->student_id : $row->user_id,
                    'user_id'      => count($attendanceData) > 0 && isset($row->type) ? $row->student_id : $row->user_id,
                    'admission_no' => $row->user ? ($row->user->student->admission_no ?? '') : ($row->admission_no ?? ''),
                    'roll_no'      => $row->user ? ($row->user->student->roll_number ?? '') : ($row->roll_number ?? ''),
                    'name'         => $row->user ? ($row->user->first_name ?? '') . ' ' . ($row->user->last_name ?? '') : '',
                    'type'         => $type,
                ];
                $no++;
            }

            $bulkData['total'] = $total;
            $bulkData['data'] = $rows;

            return ResponseService::successResponse('Data Fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffAttendanceApiController -> show");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['class-teacher', 'attendance-create', 'attendance-edit']);
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric',
            'date'             => 'required|date',
            'attendance_data'  => 'required|array',
            'attendance_data.*.student_id' => 'required|numeric',
            'attendance_data.*.type' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $attendanceData = array();
            $sessionYear = $this->cache->getDefaultSessionYear();
            $student_ids = array();
            
            foreach ($request->attendance_data as $value) {
                $data = (object)$value;
                $attendanceData[] = array(
                    "id"               => $data->id ?? null,
                    'class_section_id' => $request->class_section_id,
                    'student_id'       => $data->student_id,
                    'session_year_id'  => $sessionYear->id,
                    'type'             => $request->holiday ?? $data->type,
                    'date'             => date('Y-m-d', strtotime($request->date)),
                );

                if ($data->type == 0) {
                    $student_ids[] = $data->student_id;
                }
            }
            
            $this->attendance->upsert($attendanceData, ["id"], ["class_section_id", "student_id", "session_year_id", "type", "date"]);

            DB::commit();
            
            if ($request->absent_notification && !empty($student_ids)) {
                $user = $this->student->builder()->whereIn('user_id', $student_ids)->pluck('guardian_id')->toArray();
                $date = Carbon::parse(date('Y-m-d', strtotime($request->date)))->format('F jS, Y');
                $title = 'Absent';
                $body = 'Your child is absent on ' . $date;
                $type = "attendance";

                send_notification($user, $title, $body, $type);
            }

            return ResponseService::successResponse('Attendance Stored Successfully');
        } catch (Throwable $e) {
            if (Str::contains($e->getMessage(), [
                'does not exist','file_get_contents'
            ])) {
                DB::commit();
                return ResponseService::warningResponse("Data Stored successfully. But App push notification not send.");
            } else {
                DB::rollback();
                ResponseService::logErrorResponse($e, "StaffAttendanceApiController -> store");
                return ResponseService::errorResponse();
            }
        }
    }

    public function attendanceReport(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['class-teacher', 'attendance-list']);

        $offset = request('offset', 0);
        $limit = request('limit');
        $sort = request('sort', 'student_id');
        $order = request('order', 'ASC');
        $search = request('search');
        $attendanceType = request('attendance_type');

        $class_section_id = request('class_section_id');
        $date = request('date') ? date('Y-m-d', strtotime(request('date'))) : '';

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric',
            'date'             => 'required|date',
        ]);
        
        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $sessionYear = $this->cache->getDefaultSessionYear();

            $sql = $this->attendance->builder()->where(['date' => $date, 'class_section_id' => $class_section_id])->with('user.student')
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('id', 'LIKE', "%$search%")
                                ->orwhere('student_id', 'LIKE', "%$search%")
                                ->orWhereHas('user', function ($q) use ($search) {
                                    $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'")
                                        ->orwhere('first_name', 'LIKE', "%$search%")
                                        ->orwhere('last_name', 'LIKE', "%$search%");
                                })->orWhereHas('user.student', function ($q) use ($search) {
                                    $q->where('admission_no', 'LIKE', "%$search%")
                                        ->orwhere('id', 'LIKE', "%$search%")
                                        ->orwhere('user_id', 'LIKE', "%$search%")
                                        ->orwhere('roll_number', 'LIKE', "%$search%");
                                });
                        });
                    });
                })
                ->when($attendanceType != null, function ($query) use ($attendanceType) {
                    $query->where('type', $attendanceType);
                });
                
            $sql = $sql->whereHas('user.student', function ($q) use ($sessionYear) {
                $q->where('session_year_id', $sessionYear->id);
            });
            
            $total = $sql->count();
            $sql->orderBy($sort, $order);

            if ($limit) {
                $sql->skip($offset)->take($limit);
            }
            $res = $sql->get();

            $rows = [];
            foreach ($res as $row) {
                $tempRow = $row->toArray();
                $tempRow['admission_no'] = $row->user ? ($row->user->student->admission_no ?? '') : '';
                $tempRow['roll_no'] = $row->user ? ($row->user->student->roll_number ?? '') : '';
                $tempRow['name'] = $row->user ? ($row->user->first_name ?? '') . ' ' . ($row->user->last_name ?? '') : '';
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];
            
            return ResponseService::successResponse('Attendance Report Fetched Successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffAttendanceApiController -> attendanceReport");
            return ResponseService::errorResponse();
        }
    }
}
