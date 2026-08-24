<?php

namespace App\Http\Controllers;

use App\Repositories\StaffAttendance\StaffAttendanceInterface;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Throwable;

class StaffAttendanceController extends Controller
{
    private StaffAttendanceInterface $staffAttendance;

    public function __construct(StaffAttendanceInterface $staffAttendance)
    {
        $this->staffAttendance = $staffAttendance;
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['staff-attendance-list']);
        return view('staff_attendance.index');
    }

    public function show(Request $request)
    {
        $user = Auth::user();
        // If user doesn't have permission to view all, and they are not viewing their own, then deny
        if (!$user->can('staff-attendance-list') && $request->staff_id != $user->id) {
            ResponseService::noAnyPermissionThenSendJson(['staff-attendance-list']);
        }

        if (empty($user->school_id)) {
            return response()->json(['total' => 0, 'rows' => []]);
        }

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'DESC');

        $sql = $this->staffAttendance->builder()->with('user:id,first_name,last_name,email,mobile')
            ->when($request->date, function ($q) use ($request) {
                $date = date('Y-m-d', strtotime($request->date));
                $q->whereDate('date', $date);
            })
            ->when($request->staff_id, function ($q) use ($request) {
                $q->where('user_id', $request->staff_id);
            });

        // Force self ID if no permission to see all
        if (!$user->can('staff-attendance-list')) {
            $sql->where('user_id', $user->id);
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        foreach ($res as $row) {
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->user->full_name;
            $tempRow['date'] = $row->date;
            $tempRow['check_in'] = $row->check_in ? Carbon::parse($row->check_in)->format('H:i:s') : '-';
            $tempRow['check_out'] = $row->check_out ? Carbon::parse($row->check_out)->format('H:i:s') : '-';
            $tempRow['check_in_location'] = $row->check_in_location;
            $tempRow['check_out_location'] = $row->check_out_location;
            if ($row->type === 'Work From Home') {
                $tempRow['status'] = 'Work From Home';
            } elseif ($row->status == 1) {
                $tempRow['status'] = 'Present';
            } elseif ($row->status == 2) {
                $tempRow['status'] = 'Late';
            } elseif ($row->status == 3) {
                $tempRow['status'] = 'Half Day';
            } else {
                $tempRow['status'] = 'Absent';
            }
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function check(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:in,out',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $user = Auth::user();
            $today = Carbon::now()->toDateString();
            $currentTime = Carbon::now()->toDateTimeString();

            if ($request->type == 'in') {
                $attendance = $this->staffAttendance->builder()->where('user_id', $user->id)->where('date', $today)->first();
                if ($attendance) {
                    return ResponseService::errorResponse('Already checked in for today');
                }

                $data = [
                    'user_id' => $user->id,
                    'school_id' => $user->school_id,
                    'date' => $today,
                    'check_in' => $currentTime,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'check_in_location' => $request->address ?? null,
                    'check_in_ip' => $request->ip(),
                    'status' => 1
                ];
                $this->staffAttendance->create($data);
                return ResponseService::successResponse('Successfully Checked In');
            } else {
                $attendance = $this->staffAttendance->builder()->where('user_id', $user->id)->where('date', $today)->first();
                if (!$attendance) {
                    return ResponseService::errorResponse('Please check in first');
                }
                if ($attendance->check_out) {
                    return ResponseService::errorResponse('Already checked out for today');
                }

                $data = [
                    'check_out' => $currentTime,
                    'check_out_latitude' => $request->latitude,
                    'check_out_longitude' => $request->longitude,
                    'check_out_location' => $request->address ?? null,
                    'check_out_ip' => $request->ip(),
                ];
                $this->staffAttendance->update($attendance->id, $data);
                return ResponseService::successResponse('Successfully Checked Out');
            }
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }

    public function myAttendance()
    {
        return view('staff_attendance.my_attendance');
    }

    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $userId = $request->user_id;
            $user = User::find($userId);
            $today = Carbon::now()->toDateString();
            $currentTime = Carbon::now()->toDateTimeString();

            // Check if already checked in today
            $attendance = $this->staffAttendance->builder()->where('user_id', $userId)->where('date', $today)->first();

            if (!$attendance) {
                // Check In
                $data = [
                    'user_id' => $userId,
                    'school_id' => $user->school_id,
                    'date' => $today,
                    'check_in' => $currentTime,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'check_in_location' => $request->address ?? null,
                    'check_in_ip' => $request->ip(),
                    'scanned_by' => Auth::id(),
                    'status' => 1
                ];
                $this->staffAttendance->create($data);
                return ResponseService::successResponse("{$user->full_name} Successfully Checked In", $user);
            } else {
                // Check Out
                if ($attendance->check_out) {
                    return ResponseService::errorResponse("{$user->full_name} already checked out for today");
                }

                $data = [
                    'check_out' => $currentTime,
                    'check_out_latitude' => $request->latitude,
                    'check_out_longitude' => $request->longitude,
                    'check_out_location' => $request->address ?? null,
                    'check_out_ip' => $request->ip(),
                    'scanned_by' => Auth::id(),
                ];
                $this->staffAttendance->update($attendance->id, $data);
                return ResponseService::successResponse("{$user->full_name} Successfully Checked Out", $user);
            }
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }

    public function monthWiseIndex()
    {
        ResponseService::noAnyPermissionThenRedirect(['staff-attendance-list']);
        return view('staff_attendance.month_wise');
    }

    public function monthWiseList(Request $request)
    {
        $user = Auth::user();
        if (!$user->can('staff-attendance-list')) {
            ResponseService::noAnyPermissionThenSendJson(['staff-attendance-list']);
        }

        $month = $request->month;
        $date = Carbon::create(null, $month, 1);
        $school_id = $user->school_id;

        // Fetch all staff for this school
        $staffUsers = User::where('school_id', $school_id)
            ->where('status', 1)
            ->has('staff')
            ->whereHas('roles', function($q) {
                $q->whereNotIn('name', ['Student', 'Parent']);
            })->orderBy('first_name', 'ASC')->get();
            
        $total = $staffUsers->count();
        $rows = array();
        
        $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $date->copy()->endOfMonth()->format('Y-m-d');
        $staffUserIds = $staffUsers->pluck('id')->toArray();
        
        $allAttendances = $this->staffAttendance->builder()
            ->whereIn('user_id', $staffUserIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('user_id');
        
        foreach ($staffUsers as $staffUser) {            
            $staffAttendance = ['full_name' => $staffUser->full_name, 'user_id' => $staffUser->id];
            $userAttendances = $allAttendances->has($staffUser->id) ? $allAttendances[$staffUser->id]->keyBy('date') : collect();
            
            for ($day=1; $day <= $date->daysInMonth; $day++) {
                $currentDate = $date->copy()->day($day)->format('Y-m-d');
                $attendance = $userAttendances->has($currentDate) ? $userAttendances[$currentDate] : null;
                
                if ($attendance) {
                    if ($attendance->type === 'Work From Home') {
                        $staffAttendance["day_$day"] = 'W';
                    } elseif ($attendance->status == 1) {
                        $staffAttendance["day_$day"] = 'P';
                    } elseif ($attendance->status == 3) {
                        $staffAttendance["day_$day"] = 'H';
                    } else {
                        $staffAttendance["day_$day"] = 'A';
                    }
                } else {
                    $staffAttendance["day_$day"] = null;
                }
            }
            $rows[] = $staffAttendance;
        }

        $bulkData = array();
        $bulkData['total'] = $total;
        $bulkData['rows'] = $rows;
        
        \Log::info("Staff Attendance Month Wise List:", ['total' => $total, 'rows_count' => count($rows), 'school_id' => $school_id]);
        
        return response()->json($bulkData);
    }

    public function storeMonthWise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required',
            'type' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $user = Auth::user();
            if (!$user->can('staff-attendance-list')) {
                return ResponseService::errorResponse('Permission Denied');
            }

            $attendance = $this->staffAttendance->builder()
                ->where('user_id', $request->user_id)
                ->where('date', $request->date)
                ->first();

            $status = $request->status;
            $type = $request->type;

            if (!$attendance) {
                $targetUser = User::find($request->user_id);
                $data = [
                    'user_id' => $request->user_id,
                    'school_id' => $targetUser->school_id,
                    'date' => $request->date,
                    'status' => $status,
                    'type' => $type
                ];
                $this->staffAttendance->create($data);
            } else {
                $data = [
                    'status' => $status,
                    'type' => $type
                ];
                $this->staffAttendance->update($attendance->id, $data);
            }

            return ResponseService::successResponse('Successfully Saved');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }

    public function storeBulkMonthWise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendances' => 'required|array',
            'attendances.*.user_id' => 'required|exists:users,id',
            'attendances.*.date' => 'required|date',
            'attendances.*.status' => 'required',
            'attendances.*.type' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $user = Auth::user();
            if (!$user->can('staff-attendance-list')) {
                return ResponseService::errorResponse('Permission Denied');
            }

            foreach($request->attendances as $att) {
                $attendance = $this->staffAttendance->builder()
                    ->where('user_id', $att['user_id'])
                    ->where('date', $att['date'])
                    ->first();

                $status = $att['status'];
                $type = $att['type'] ?? '';

                if (!$attendance) {
                    $targetUser = User::find($att['user_id']);
                    $data = [
                        'user_id' => $att['user_id'],
                        'school_id' => $targetUser->school_id,
                        'date' => $att['date'],
                        'status' => $status,
                        'type' => $type
                    ];
                    $this->staffAttendance->create($data);
                } else {
                    $data = [
                        'status' => $status,
                        'type' => $type
                    ];
                    $this->staffAttendance->update($attendance->id, $data);
                }
            }

            return ResponseService::successResponse('Successfully Saved');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }

    public function qr()
    {
        ResponseService::noPermissionThenRedirect('staff-attendance-qr');
        return view('staff_attendance.qr');
    }

    public function generateQr(Request $request)
    {
        ResponseService::noPermissionThenSendJson('staff-attendance-qr');
        
        // As requested: same as DateTime.now().millisecondsSinceEpoch ~/ 1000
        $issuedAt = Carbon::now()->timestamp;

        return response()->json(['error' => false, 'token' => (string) $issuedAt]);
    }
}
