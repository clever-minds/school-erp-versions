<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Leave\LeaveInterface;
use App\Repositories\LeaveDetail\LeaveDetailInterface;
use App\Repositories\LeaveMaster\LeaveMasterInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\User\UserInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use App\Repositories\Files\FilesInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class StaffLeaveApiController extends Controller
{
    private LeaveInterface $leave;
    private SessionYearInterface $sessionYear;
    private LeaveDetailInterface $leaveDetail;
    private CachingService $cache;
    private LeaveMasterInterface $leaveMaster;
    private UserInterface $user;
    private FilesInterface $files;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

    public function __construct(
        LeaveInterface $leave,
        SessionYearInterface $sessionYear,
        LeaveDetailInterface $leaveDetail,
        CachingService $cache,
        LeaveMasterInterface $leaveMaster,
        UserInterface $user,
        FilesInterface $files,
        SessionYearsTrackingsService $sessionYearsTrackingsService
    ) {
        $this->leave = $leave;
        $this->sessionYear = $sessionYear;
        $this->leaveDetail = $leaveDetail;
        $this->cache = $cache;
        $this->leaveMaster = $leaveMaster;
        $this->user = $user;
        $this->files = $files;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
    }

    public function index()
    {
        $request = request();
        ResponseService::noPermissionThenSendJson('leave-list');

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $session_year_id = request('session_year_id');
        $filter_upcoming = request('filter_upcoming');
        $month_id = request('month_id');

        try {
            $sql = $this->leave->builder()->with('leave_detail', 'file')->where('user_id', Auth::user()->id)
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('id', 'LIKE', "%$search%")
                                ->orwhere('reason', 'LIKE', "%$search%")
                                ->orwhere('from_date', 'LIKE', "%$search%")
                                ->orwhere('to_date', 'LIKE', "%$search%");
                        });
                    });
                });

            if ($session_year_id) {
                $sql->whereHas('leave_master', function ($q) use ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                });
            }

            $sql = $sql->withCount(['leave_detail as full_leave' => function ($q) {
                $q->where('type', 'Full');
            }]);

            $sql = $sql->withCount(['leave_detail as half_leave' => function ($q) {
                $q->whereNot('type', 'Full');
            }]);

            if ($filter_upcoming) {
                if ($filter_upcoming == 'Today') {
                    $sql->whereDate('from_date', '<=', Carbon::now()->format('Y-m-d'))->whereDate('to_date', '>=', Carbon::now()->format('Y-m-d'));
                }
                if ($filter_upcoming == 'Tomorrow') {
                    $tomorrow_date = Carbon::now()->addDay()->format('Y-m-d');
                    $sql->whereHas('leave_detail', function ($q) use ($tomorrow_date) {
                        $q->whereDate('date', '<=', $tomorrow_date)->whereDate('date', '>=', $tomorrow_date);
                    });
                }
                if ($filter_upcoming == 'Upcoming') {
                    $upcoming_date = Carbon::now()->addDays(1)->format('Y-m-d');
                    $sql->whereHas('leave_detail', function ($q) use ($upcoming_date) {
                        $q->whereDate('date', '>', $upcoming_date);
                    });
                }
            }

            if ($month_id) {
                $sql->whereHas('leave_detail', function ($q) use ($month_id) {
                    $q->whereMonth('date', $month_id);
                });
            }

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $rows = [];
            foreach ($res as $row) {
                $tempRow = $row->toArray();
                $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];
            
            return ResponseService::successResponse('Leaves fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffLeaveApiController -> index");
            return ResponseService::errorResponse();
        }
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('leave-create');

        $validator = Validator::make($request->all(), [
            'reason'  => 'required',
            'from_date' => 'required',
            'to_date' => 'required|after_or_equal:from_date',
            'leave_master_id' => 'required',
            'type' => 'required',
            'files.*' => 'nullable',
        ], [
            'leave_master_id.required' => 'Kindly contact the school admin to update settings for continued access.',
            'type.required' => 'Kindly select different dates as the ones mentioned are already allocated as holidays.'
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            
            $data = [
                'user_id' => Auth::user()->id,
                'reason' => $request->reason,
                'from_date' => date('Y-m-d', strtotime($request->from_date)),
                'to_date' => date('Y-m-d', strtotime($request->to_date)),
                'leave_master_id' => $request->leave_master_id
            ];
            $leave = $this->leave->create($data);
            
            $leaveDetails = [];
            if (is_array($request->type) || is_object($request->type)) {
                foreach ($request->type as $key => $type) {
                    $leaveDetails[] = [
                        'leave_id' => $leave->id,
                        'date' => date('Y-m-d', strtotime($key)),
                        'type' => is_array($type) ? $type[0] : $type
                    ];
                }
                $this->leaveDetail->createBulk($leaveDetails);
            }

            if ($request->hasFile('files')) {
                $fileData = [];
                $leaveModelAssociate = $this->files->model()->modal()->associate($leave);
            
                foreach ($request->file('files') as $file_upload) {
                    $tempFileData = [
                        'modal_type' => $leaveModelAssociate->modal_type,
                        'modal_id'   => $leaveModelAssociate->modal_id,
                        'file_name'  => $file_upload->getClientOriginalName(),
                        'type'       => 1,
                        'file_url'   => $file_upload
                    ];
                    $fileData[] = $tempFileData;
                }
                $this->files->createBulk($fileData);
            }

            $sessionYear = $this->cache->getDefaultSessionYear();
            $semester = $this->cache->getDefaultSemesterData();
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Leave', $leave->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, $semester ? $semester->id : null);

            $user = $this->user->builder()->where(function ($query) {
                $query->whereHas('roles.permissions', function ($q) {
                    $q->where('name', 'approve-leave');
                })->orWhereHas('roles', function ($q) {
                    $q->where('name', 'Principal');
                });
            })->pluck('id');
            
            $type_push = "Leave";
            $title = Auth::user()->full_name . ' has submitted a new leave request.';
            $body = $request->reason;

            DB::commit();
            send_notification($user, $title, $body, $type_push);

            return ResponseService::successResponse('Leave applied Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                return ResponseService::warningResponse("Leave applied successfully. But App push notification not send.");
            }
            ResponseService::logErrorResponse($e, "StaffLeaveApiController -> store");
            return ResponseService::errorResponse();
        }
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('leave-edit');

        $validator = Validator::make($request->all(), [
            'reason'  => 'required',
            'from_date' => 'required',
            'to_date' => 'required|after_or_equal:from_date',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $data = [
                'reason' => $request->reason,
                'from_date' => date('Y-m-d', strtotime($request->from_date)),
                'to_date' => date('Y-m-d', strtotime($request->to_date)),
            ];
            $this->leave->update($id, $data);
            DB::commit();
            return ResponseService::successResponse('Leave Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffLeaveApiController -> update");
            return ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('leave-delete');
        try {
            DB::beginTransaction();
            $leave = $this->leave->findById($id);
            
            if ($leave->file) {
                foreach ($leave->file as $file) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }
                }
                $leave->file()->delete();
            }
            
            $this->leaveDetail->builder()->where('leave_id', $id)->delete();
            $this->leave->deleteById($id);
            
            $sessionYear = $this->cache->getDefaultSessionYear();
            $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Leave', $id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
            
            DB::commit();
            return ResponseService::successResponse('Leave Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffLeaveApiController -> destroy");
            return ResponseService::errorResponse();
        }
    }

    public function leaveRequests(Request $request)
    {
        ResponseService::noPermissionThenSendJson('approve-leave');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $session_year_id = request('session_year_id');
        $month_id = request('month_id');

        try {
            $sql = $this->leave->builder()->with('user.staff.user', 'leave_detail', 'file', 'leave_master')
                ->where('status', '!=', 1)
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%");
                        })->orWhereHas('user', function ($q) use ($search) {
                            $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'")
                                ->orWhereHas('staff', function ($q) use ($search) {
                                    $q->whereHas('user', function ($q) use ($search) {
                                        $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'");
                                    });
                                });
                        });
                    });
                });

            if ($session_year_id) {
                $sql->whereHas('leave_master', function ($q) use ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                });
            }

            if ($month_id) {
                $sql->whereHas('leave_detail', function ($q) use ($month_id) {
                    $q->whereMonth('date', $month_id);
                });
            }

            $sql = $sql->withCount(['leave_detail as full_leave' => function ($q) {
                $q->where('type', 'Full');
            }]);

            $sql = $sql->withCount(['leave_detail as half_leave' => function ($q) {
                $q->whereNot('type', 'Full');
            }]);

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $rows = [];
            foreach ($res as $row) {
                $tempRow = $row->toArray();
                $tempRow['user_name'] = $row->user ? $row->user->first_name . ' ' . $row->user->last_name : '';
                $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];
            
            return ResponseService::successResponse('Leave requests fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffLeaveApiController -> leaveRequests");
            return ResponseService::errorResponse();
        }
    }

    public function updateLeaveStatus(Request $request)
    {
        ResponseService::noPermissionThenSendJson('approve-leave');
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'status' => 'required',
        ]);
        
        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $data = [
                'status' => $request->status
            ];
            $leave = $this->leave->update($request->id, $data);

            if ($request->status == 1) { // 1 = Approved
                $user = $this->user->builder()->where('id', $leave->user_id)->pluck('id');
                $type_push = "Leave";
                $title = 'Your Leave is Approved';
                $body = 'Your Leave is Approved for Reason : ' . $leave->reason;
                send_notification($user, $title, $body, $type_push);
            }
            if ($request->status == 2) { // 2 = Rejected
                $user = $this->user->builder()->where('id', $leave->user_id)->pluck('id');
                $type_push = "Leave";
                $title = 'Your Leave is Rejected';
                $body = 'Your Leave is Rejected for Reason : ' . $leave->reason;
                send_notification($user, $title, $body, $type_push);
            }
            
            DB::commit();
            return ResponseService::successResponse('Leave Status Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                return ResponseService::warningResponse("Status Updated successfully. But App push notification not send.");
            }
            ResponseService::logErrorResponse($e, "StaffLeaveApiController -> updateLeaveStatus");
            return ResponseService::errorResponse();
        }
    }
    
    public function leaveReport(Request $request)
    {
        ResponseService::noPermissionThenSendJson('leave-list');

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $session_year_id = request('session_year_id');

        try {
            $sql = $this->leave->builder()->with('user', 'leave_detail', 'file', 'leave_master')
                ->where('status', 1)
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('id', 'LIKE', "%$search%")->orwhere('reason', 'LIKE', "%$search%")->orwhere('from_date', 'LIKE', "%$search%")->orwhere('to_date', 'LIKE', "%$search%");
                        })->orWhereHas('user', function ($q) use ($search) {
                            $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'");
                        });
                    });
                });

            if ($session_year_id) {
                $sql->whereHas('leave_master', function ($q) use ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                });
            }

            $sql = $sql->withCount(['leave_detail as full_leave' => function ($q) {
                $q->where('type', 'Full');
            }]);

            $sql = $sql->withCount(['leave_detail as half_leave' => function ($q) {
                $q->whereNot('type', 'Full');
            }]);

            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $rows = [];
            foreach ($res as $row) {
                $tempRow = $row->toArray();
                $tempRow['user_name'] = $row->user ? $row->user->first_name . ' ' . $row->user->last_name : '';
                $tempRow['days'] = $row->full_leave + ($row->half_leave / 2);
                $rows[] = $tempRow;
            }

            $bulkData = [
                'total' => $total,
                'data' => $rows
            ];
            
            return ResponseService::successResponse('Leave Report fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffLeaveApiController -> leaveReport");
            return ResponseService::errorResponse();
        }
    }

    public function leaveMasterIndex(Request $request)
    {
        ResponseService::noPermissionThenSendJson('school-setting-manage');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        try {
            $sql = $this->leaveMaster->builder()->with('session_year')
                ->when(request('session_year_id') != null, function ($query) use ($request) {
                    $query->where('session_year_id', $request->session_year_id);
                })->where(function($q) use($search){
                    $q->when($search, function ($query) use ($search) {
                        $query->where('leaves','LIKE', "%$search%")
                        ->orWhere('holiday','LIKE', "%$search%");
                    });
                });
                
            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = [
                'total' => $total,
                'data' => $res
            ];
            return ResponseService::successResponse('Leave Allowances fetched successfully', $bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffLeaveApiController -> leaveMasterIndex");
            return ResponseService::errorResponse();
        }
    }

    public function leaveMasterStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('school-setting-manage');
        $validator = Validator::make($request->all(), [
            'leaves' => 'required|numeric',
            'holiday_days' => 'required|array',
            'session_year_id' => 'required|unique:leave_masters'
        ],[
            'session_year_id.unique' => 'This session year has already been taken.'
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $day = implode(',', $request->holiday_days);
            $data = [
                'leaves' => $request->leaves,
                'holiday' => $day,
                'session_year_id' => $request->session_year_id,
            ];

            $this->leaveMaster->create($data);
            DB::commit();
            return ResponseService::successResponse('Leave Allowance Created Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffLeaveApiController -> leaveMasterStore");
            return ResponseService::errorResponse();
        }
    }

    public function leaveMasterUpdate(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('school-setting-manage');
        $validator = Validator::make($request->all(), [
            'leaves' => 'required|numeric',
            'holiday_days' => 'required|array',
            'session_year_id' => 'required|unique:leave_masters,session_year_id,'.$id
        ],[
            'session_year_id.unique' => 'This session year has already been taken.'
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();
            $day = implode(',', $request->holiday_days);
            $data = [
                'leaves' => $request->leaves,
                'holiday' => $day,
                'session_year_id' => $request->session_year_id,
            ];

            $this->leaveMaster->update($id, $data);
            DB::commit();
            return ResponseService::successResponse('Leave Allowance Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "StaffLeaveApiController -> leaveMasterUpdate");
            return ResponseService::errorResponse();
        }
    }

    public function leaveMasterDestroy($id)
    {
        ResponseService::noPermissionThenSendJson('school-setting-manage');        
        try {
            $leaveMaster = $this->leaveMaster->findById($id);
            if (count($leaveMaster->leave)) {
                return ResponseService::errorResponse('cannot_delete_because_data_is_associated_with_other_data');
            } else {
                $this->leaveMaster->deleteById($id);
            }
            return ResponseService::successResponse('Leave Allowance Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "StaffLeaveApiController -> leaveMasterDestroy");
            return ResponseService::errorResponse();
        }
    }
}
