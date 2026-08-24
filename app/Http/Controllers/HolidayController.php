<?php

namespace App\Http\Controllers;

use App\Repositories\Holiday\HolidayInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use App\Services\CachingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Throwable;

class HolidayController extends Controller
{

    private HolidayInterface $holiday;
    private SessionYearInterface $sessionYear;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;
    private CachingService $cache;

    public function __construct(HolidayInterface $holiday, SessionYearInterface $sessionYear, SessionYearsTrackingsService $sessionYearsTrackingsService, CachingService $cache)
    {
        $this->holiday = $holiday;
        $this->sessionYear = $sessionYear;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
        $this->cache = $cache;
    }

    public function index()
    {
        ResponseService::noFeatureThenRedirect('Holiday Management');
        ResponseService::noPermissionThenRedirect('holiday-list');
        $sessionYears = $this->sessionYear->all();
        $current_sessionYear = $this->cache->getDefaultSessionYear();
        $months = sessionYearWiseMonth();
        $classes = \App\Models\ClassSchool::all();
        return view('holiday.index', compact('sessionYears', 'months', 'current_sessionYear','classes'));
    }


  public function store(Request $request)
{
    ResponseService::noFeatureThenRedirect('Holiday Management');
    ResponseService::noPermissionThenRedirect('holiday-create');

    // Validation
    if ($request->type == 'holiday') {

        $validator = Validator::make($request->all(), [
            'date'  => 'required',
            'title' => 'required'
        ]);

    } else {

        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'class_ids' => 'required|array'
        ]);

    }

    if ($validator->fails()) {
        ResponseService::errorResponse($validator->errors()->first());
    }

    try {

        $sessionYear = $this->cache->getDefaultSessionYear();

        $data = $request->all();

        // convert class_ids array to comma separated string
        if ($request->has('class_ids')) {
            $data['class_ids'] = implode(',', $request->class_ids);
        }

        // date validation only for normal holiday
        if ($request->type == 'holiday') {
            
            $dates = explode(' - ', $request->date);
            $startDate = Carbon::parse($dates[0]);
            $endDate = isset($dates[1]) ? Carbon::parse($dates[1]) : Carbon::parse($dates[0]);

            $sessionStart = Carbon::parse($sessionYear->start_date);
            $sessionEnd   = Carbon::parse($sessionYear->end_date);

            if ($startDate->lt($sessionStart) || $endDate->gt($sessionEnd)) {
                ResponseService::errorResponse('The selected date range must fall within the current session year.');
            }

            $data['date'] = $startDate->format('Y-m-d');
            $data['end_date'] = $endDate->format('Y-m-d');
            
            $holiday = $this->holiday->create($data);

            $this->sessionYearsTrackingsService->storeSessionYearsTracking(
                'App\Models\Holiday',
                $holiday->id,
                Auth::user()->id,
                $sessionYear->id,
                Auth::user()->school_id,
                null
            );

        } else {
            // saturday holiday → no specific date
            $data['date'] = null;
            $holiday = $this->holiday->create($data);

            $this->sessionYearsTrackingsService->storeSessionYearsTracking(
                'App\Models\Holiday',
                $holiday->id,
                Auth::user()->id,
                $sessionYear->id,
                Auth::user()->school_id,
                null
            );
        }

        ResponseService::successResponse('Data Stored Successfully');

    } catch (Throwable $e) {

        ResponseService::logErrorResponse($e, "Holiday Controller -> Store Method");
        ResponseService::errorResponse();

    }
}
  public function update($id, Request $request)
{
    ResponseService::noFeatureThenRedirect('Holiday Management');
    ResponseService::noPermissionThenSendJson('holiday-edit');

    // Validation
    if ($request->type == 'holiday') {
        $validator = Validator::make($request->all(), [
            'date'  => 'required',
            'title' => 'required'
        ]);
    } else {
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'class_ids' => 'required|array'
        ]);
    }

    if ($validator->fails()) {
        ResponseService::errorResponse($validator->errors()->first());
    }

    try {
        $sessionYear = $this->cache->getDefaultSessionYear();

        $data = $request->all();

        // Convert class_ids array to comma separated string
        if ($request->has('class_ids')) {
            $data['class_ids'] = implode(',', $request->class_ids);
        }

        // Date validation only for normal holiday
        if ($request->type == 'holiday') {
            $dates = explode(' - ', $request->date);
            $startDate = Carbon::parse($dates[0]);
            $endDate = isset($dates[1]) ? Carbon::parse($dates[1]) : Carbon::parse($dates[0]);
            
            $start = Carbon::parse($sessionYear->start_date);
            $end   = Carbon::parse($sessionYear->end_date);

            if ($startDate->lt($start) || $endDate->gt($end)) {
                ResponseService::errorResponse('The selected date range must fall within the current session year.');
            }
            
            $data['date'] = $startDate->format('Y-m-d');
            $data['end_date'] = $endDate->format('Y-m-d');
        } else {
            // Saturday holiday → no specific date
            $data['date'] = null;
        }

        // Update holiday
        $this->holiday->update($id, $data);

        // Store session year tracking for audit
        $this->sessionYearsTrackingsService->storeSessionYearsTracking(
            'App\Models\Holiday',
            $id,
            Auth::user()->id,
            $sessionYear->id,
            Auth::user()->school_id,
            null
        );

        ResponseService::successResponse('Data Updated Successfully');

    } catch (Throwable $e) {
        ResponseService::logErrorResponse($e, "Holiday Controller -> Update Method");
        ResponseService::errorResponse();
    }
}

    // TODO : Remove this if not necessary
    // public function holiday_view()
    // {
    //     return view('holiday.list');
    // }

 public function show(Request $request)
{
    ResponseService::noFeatureThenRedirect('Holiday Management');
    ResponseService::noPermissionThenRedirect('holiday-list');

    $offset = request('offset', 0);
    $limit = request('limit', 10);
    $sort = request('sort', 'id');
    $order = request('order', 'DESC');
    $search = request('search');
    $session_year_id = request('session_year_id');
    $month = request('month');

    $sessionYear = $this->sessionYear->findById($session_year_id);

    $sql = $this->holiday->builder()
       

        ->where(function ($query) use ($search) {

            if ($search) {
                $query->where(function ($q) use ($search) {

                    $q->where('id', 'LIKE', "%$search%")
                        ->orWhere('title', 'LIKE', "%$search%")
                        ->orWhere('description', 'LIKE', "%$search%")
                        ->orWhere('date', 'LIKE', "%$search%");
                });
            }
        })

        ->when($session_year_id, function ($query) use ($sessionYear) {

            $query->where(function ($q) use ($sessionYear) {

                $q->whereBetween('date', [
                    $sessionYear->start_date,
                    $sessionYear->end_date
                ])
                    ->orWhereIn('type', ['saturday_all', 'saturday_2_4']);
            });
        })

        ->when($month, function ($query) use ($month) {
            $query->where(function($q) use ($month) {
                $q->whereMonth('date', $month)
                  ->orWhereMonth('end_date', $month);
            });
        });

    $total = $sql->count();

    $sql->orderBy($sort, $order)->skip($offset)->take($limit);

    $res = $sql->get();

    $bulkData = [];
    $bulkData['total'] = $total;

    $rows = [];
    $no = 1;

    foreach ($res as $row) {

        $operate = BootstrapTableService::editButton(route('holiday.update', $row->id));
        $operate .= BootstrapTableService::deleteButton(route('holiday.destroy', $row->id));

        $tempRow = [];

        $tempRow['id'] = $row->id;
        $tempRow['no'] = $no++;
       
        $tempRow['title'] = $row->title;
        $tempRow['type'] = $row->type;
        $tempRow['class_ids'] = $row->class_ids;
        $tempRow['description'] = $row->description;

        // Holiday Type
        if ($row->type == 'holiday') {
            $raw_date = $row->getRawOriginal('date');
            $raw_end_date = $row->getRawOriginal('end_date');
            if (!empty($raw_end_date) && $raw_date != $raw_end_date) {
                $tempRow['date'] = date('d-m-Y', strtotime($raw_date)) . ' to ' . date('d-m-Y', strtotime($raw_end_date));
            } else {
                $tempRow['date'] = date('d-m-Y', strtotime($raw_date));
            }
            
            $tempRow['start_date'] = date('d-m-Y', strtotime($raw_date));
            $tempRow['end_date'] = !empty($raw_end_date) ? date('d-m-Y', strtotime($raw_end_date)) : date('d-m-Y', strtotime($raw_date));
            
            $tempRow['holiday_type'] = 'Normal Holiday';
        } elseif ($row->type == 'saturday_all') {
             $tempRow['date'] = "";
            $tempRow['holiday_type'] = 'All Saturday Off';
        } elseif ($row->type == 'saturday_2_4') {
            $tempRow['holiday_type'] = '2nd & 4th Saturday Off';
              $tempRow['date'] = "";
        }

        // Class List
        if ($row->class_ids) {

                $classIds = explode(',', $row->class_ids);

                $classNames = \App\Models\ClassSchool::whereIn('id', $classIds)
                    ->pluck('name')
                    ->implode(', ');

                $tempRow['class'] = $classNames;

            } else {

                $tempRow['class'] = '';

            }
        $tempRow['operate'] = $operate;

        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;

    return response()->json($bulkData);
}

    public function destroy($id)
    {
        ResponseService::noFeatureThenRedirect('Holiday Management');
        ResponseService::noPermissionThenSendJson('holiday-delete');
        try {
            $this->holiday->deleteById($id);
            $sessionYear = $this->cache->getDefaultSessionYear();
            $this->sessionYearsTrackingsService->deleteSessionYearsTracking('App\Models\Holiday', $id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Holiday Controller -> Delete Method");
            ResponseService::errorResponse();
        }
    }
}
