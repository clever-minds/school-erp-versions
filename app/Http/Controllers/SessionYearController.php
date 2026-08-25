<?php

namespace App\Http\Controllers;

use App\Jobs\SessionYearMigrationJob;
use App\Repositories\Chat\ChatInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Rules\uniqueForSchool;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Throwable;
use App\Models\Students;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Fee;
use App\Models\Attendance;
use App\Models\StaffAttendance;
use App\Models\TransportationAttendance;
use App\Models\Assignment;
use App\Models\OnlineExam;
use App\Models\Diary;
use App\Models\Notification;
use App\Models\Announcement;
use App\Models\Chat;
use App\Models\OnlineExamQuestionChoice;
use App\Models\OnlineExamStudentAnswer;
use App\Models\SessionYear;
use App\Models\User;

class SessionYearController extends Controller
{
    private SessionYearInterface $sessionYear;
    private CachingService $cache;
    private SchoolSettingInterface $schoolSettings;
    private ChatInterface $chat;

    public function __construct(SessionYearInterface $sessionYear, CachingService $cache, SchoolSettingInterface $schoolSettings, ChatInterface $chat)
    {
        $this->sessionYear = $sessionYear;
        $this->cache = $cache;
        $this->schoolSettings = $schoolSettings;
        $this->chat = $chat;
    }

    public function index()
    {
        ResponseService::noPermissionThenRedirect('session-year-list');
        $sessionYears = $this->cache->getAllSessionYears();
        return view('session_years.index', compact('sessionYears'));
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('session-year-create');
        $request->validate([
            'name' => ['required', new uniqueForSchool('session_years', 'name')],
            'start_date' => 'required|date_format:d-m-Y',
            'end_date' => 'required|date_format:d-m-Y|after_or_equal:start_date',
            'migrate_session_year_id' => 'nullable|exists:session_years,id',
            'migrate_options' => 'nullable|array',
            'semester_data' => 'nullable|array',
            'semester_data.*.start_date' => 'required_with:semester_data|date_format:d-m-Y',
            'semester_data.*.end_date' => 'required_with:semester_data|date_format:d-m-Y|after_or_equal:semester_data.*.start_date'
        ]);

        try {
            $sessionYears = $this->sessionYear->builder()->withTrashed()->get();
            foreach ($sessionYears as $sessionYear) {
                if (
                    Carbon::createFromFormat('d-m-Y', $request->start_date)->between($sessionYear->original_start_date, $sessionYear->original_end_date) ||
                    Carbon::createFromFormat('d-m-Y', $request->end_date)->between($sessionYear->original_start_date, $sessionYear->original_end_date) ||
                    Carbon::createFromFormat('d-m-Y', $request->start_date)->lte($sessionYear->original_start_date) && Carbon::createFromFormat('d-m-Y', $request->end_date)->gte($sessionYear->original_end_date)
                ) {
                    return response()->json(['error' => true, 'message' => __('The session year overlaps with an existing session year. (Can not be overlapped with deleted session year.)')]);
                }
            }
            $data = [
                'name' => $request->name,
                'school_id' => Auth::user()->school_id, // required if fillable
                'start_date' => Carbon::createFromFormat('d-m-Y', $request->start_date)->format('Y-m-d'),
                'end_date' => Carbon::createFromFormat('d-m-Y', $request->end_date)->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $newSessionYearId = DB::table('session_years')->insertGetId($data);

            if ($request->migrate_session_year_id && $request->migrate_options) {
                $migrateOptions = $request->migrate_options;

                // Server-side safeguard: If some classes require semesters, force it in options
                $mustRequireSemesters = DB::table('classes')
                    ->where('school_id', Auth::user()->school_id)
                    ->where('include_semesters', 1)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($mustRequireSemesters && !in_array('semesters', $migrateOptions)) {
                    $migrateOptions[] = 'semesters';
                }

                // Validation: Semester dates must be within new session year dates AND not overlap
                if ($request->semester_data && in_array('semesters', $migrateOptions)) {
                    $sessionStart = Carbon::createFromFormat('d-m-Y', $request->start_date)->startOfDay();
                    $sessionEnd = Carbon::createFromFormat('d-m-Y', $request->end_date)->endOfDay();

                    $semesters = collect($request->semester_data)->map(function ($sem) {
                        return [
                            'start' => Carbon::createFromFormat('d-m-Y', $sem['start_date'])->startOfDay(),
                            'end' => Carbon::createFromFormat('d-m-Y', $sem['end_date'])->endOfDay(),
                        ];
                    })->sortBy('start')->values();

                    foreach ($semesters as $index => $sem) {
                        // 1. Check against Session Year boundaries
                        if (!$sem['start']->between($sessionStart, $sessionEnd) || !$sem['end']->between($sessionStart, $sessionEnd)) {
                            return response()->json(['error' => true, 'message' => __('Semester dates must be within the session year range.')]);
                        }

                        // 2. Check overlap with previous semester
                        if ($index > 0) {
                            $prevSem = $semesters[$index - 1];
                            if ($sem['start']->lte($prevSem['end'])) {
                                return response()->json(['error' => true, 'message' => __('Semester dates cannot overlap with each other.')]);
                            }
                        }
                    }
                }

                SessionYearMigrationJob::dispatch(
                    Auth::user()->school_id,
                    (int)$request->migrate_session_year_id,
                    (int)$newSessionYearId,
                    $migrateOptions,
                    $request->semester_data
                );
            }

            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.ALL_SESSION_YEARS'));

            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Session Year Controller -> Store method");
            ResponseService::errorResponse();
        }
    }


    public function update($id, Request $request)
    {
        ResponseService::noPermissionThenSendJson('session-year-edit');
        $request->validate([
            'name' => ['required', new uniqueForSchool('session_years', 'name', $id)],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            DB::beginTransaction();
            $sessionYears = $this->sessionYear->builder()->withTrashed()->get();
            foreach ($sessionYears as $sessionYear) {
                if ($sessionYear->id != $id) {
                    if (
                        Carbon::createFromFormat('d-m-Y', $request->start_date)->between($sessionYear->original_start_date, $sessionYear->original_end_date) ||
                        Carbon::createFromFormat('d-m-Y', $request->end_date)->between($sessionYear->original_start_date, $sessionYear->original_end_date) ||
                        Carbon::createFromFormat('d-m-Y', $request->start_date)->lte($sessionYear->original_start_date) && Carbon::createFromFormat('d-m-Y', $request->end_date)->gte($sessionYear->original_end_date)
                    ) {
                        return response()->json(['error' => true, 'message' => __('The session year overlaps with an existing session year. (Can not be overlapped with deleted session year.)')]);
                    }
                }
            }
            DB::table('session_years')
                ->where('id', $id)
                ->update([
                    'name' => $request->name,
                    'start_date' => Carbon::createFromFormat('d-m-Y', $request->start_date)->format('Y-m-d'),
                    'end_date' => Carbon::createFromFormat('d-m-Y', $request->end_date)->format('Y-m-d'),
                    'updated_at' => now(),
                ]);

            $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.SESSION_YEAR"));
            $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.ALL_SESSION_YEARS"));

            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Session Year Controller -> Update method");
            ResponseService::errorResponse();
        }
    }

    public function show()
    {
        ResponseService::noPermissionThenRedirect('session-year-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $showDeleted = request('show_deleted');

        $sql = $this->sessionYear->builder()
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orwhere('name', 'LIKE', "%$search%")
                        ->orwhere('start_date', 'LIKE', "%$search%")
                        ->orwhere('end_date', 'LIKE', "%$search%");
                });
            })
            ->when(!empty($showDeleted), function ($query) {
                $query->onlyTrashed();
            });

        $total = $sql->count();
        if ($offset >= $total && $total > 0) {
            $lastPage = floor(($total - 1) / $limit) * $limit; // calculate last page offset
            $offset = $lastPage;
        }
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = '';
            // if ($showDeleted) {
            //     //Show Restore and Hard Delete Buttons
            //     $operate .= BootstrapTableService::restoreButton(route('session-year.restore', $row->id));
            //     $operate .= BootstrapTableService::button('fa fa-trash', '#', ['btn-gradient-danger', 'cleanup-data-btn'], ['title' => __('Permanent Delete'), 'data-id' => $row->id]);
            // } else {
            //     //Show Edit and Soft Delete Buttons
            //     if (!$row->default) {
            //         $operate .= BootstrapTableService::button('fa fa-calendar-check-o', route('session-year.default', $row->id), ['btn-gradient-success', 'default-session-year'], ["title" => trans("Set Default Session Year")]);
            //     }
            //     $operate .= BootstrapTableService::editButton(route('session-year.update', $row->id));
            //     if (!$row->default) {
            //         $operate .= BootstrapTableService::deleteButton(route('session-year.destroy', $row->id));
            //     }
            // }


            if ($showDeleted) {
                //Show Restore and Hard Delete Buttons
                $operate .= BootstrapTableService::menuRestoreButton('restore', route('session-year.restore', $row->id));
                // $operate .= BootstrapTableService::menuTrashButton('delete', route('session-year.trash', $row->id), null, ['cleanup-data-btn']);
                $operate .= BootstrapTableService::menuButton('delete', route('session-year.trash', $row->id), ['cleanup-data-btn'], ['data-id' => $row->id]);
            } else {
                //Show Edit and Soft Delete Buttons
                if (!$row->default) {
                    $operate .= BootstrapTableService::menuButton('set_as_default', route('session-year.default', $row->id), ['default-session-year']);
                }
                $operate .= BootstrapTableService::menuEditButton('edit', route('session-year.update', $row->id));
                if (!$row->default) {
                    $operate .= BootstrapTableService::menuDeleteButton('delete', route('session-year.destroy', $row->id));
                }
            }


            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = BootstrapTableService::menuItem($operate);
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }


    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('session-year-delete');
        try {
            DB::beginTransaction();
            $year = $this->sessionYear->findById($id);
            if ($year->default == 1) {
                $response = array(
                    'error' => true,
                    'message' => trans('default_session_year_cannot_delete')
                );
            } else {
                $this->sessionYear->deleteById($id);
                DB::commit();
                $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.ALL_SESSION_YEARS"));
                ResponseService::successResponse('Data Deleted Successfully');
            }
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Session Year Controller -> Delete method");
            ResponseService::errorResponse();
        }
        return response()->json($response);
    }

    public function restore(int $id)
    {
        ResponseService::noPermissionThenSendJson('session-year-delete');
        try {
            $this->sessionYear->findOnlyTrashedById($id)->restore();
            $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.ALL_SESSION_YEARS"));
            ResponseService::successResponse("Data Restored Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function trash($id)
    {
        ResponseService::noPermissionThenSendJson('session-year-delete');
        try {
            $this->sessionYear->findOnlyTrashedById($id)->forceDelete();
            $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.ALL_SESSION_YEARS"));
            ResponseService::successResponse("Data Deleted Permanently");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Session Year Controller -> Trash Method", 'cannot_delete_because_data_is_associated_with_other_data');
            ResponseService::errorResponse();
        }
    }

    public function default($id)
    {
        ResponseService::noPermissionThenRedirect('session-year-edit');
        try {
            DB::beginTransaction();
            Chat::whereNotNull('id')->delete();

            User::whereHas('roles', function ($query) {
                $query->where('name', 'Student');
            })->where('reset_request', 1)->update([
                'reset_request' => 0
            ]);

            // Change the Current Default Session Year to Non-Default Session Year
            $this->sessionYear->builder()->where(['default' => 1])->update(['default' => 0]);

            // Make new SessionYear as Default Session Year
            $this->sessionYear->builder()->where('id', $id)->update(['default' => 1]);
            $data[] = [
                "name" => 'session_year',
                "data" => $id,
                "type" => "number",
            ];
            $this->schoolSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.SESSION_YEAR"));
            $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.SEMESTER"));
            $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.ALL_SESSION_YEARS"));
            DB::commit();
            ResponseService::successResponse("Default Session has been Changed SuccessFully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function setSessionYear(Request $request)
    {
        $request->validate([
            'session_year_id' => 'required|numeric'
        ]);
        $this->cache->setSessionYear($request->session_year_id);
        return response()->json(['error' => false, 'message' => __('Session year changed successfully.')]);
    }

    public function getSemesters($session_year_id)
    {
        try {
            $semesters = DB::table('semesters')
                ->where('session_year_id', $session_year_id)
                ->where('school_id', Auth::user()->school_id)
                ->whereNull('deleted_at')
                ->get(['id', 'name', 'start_date', 'end_date']);

            $mustRequireSemesters = DB::table('classes')
                ->where('school_id', Auth::user()->school_id)
                ->where('include_semesters', 1)
                ->whereNull('deleted_at')
                ->exists();

            return response()->json([
                'semesters' => $semesters,
                'must_require_semesters' => $mustRequireSemesters
            ]);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Session Year Controller -> getSemesters method");
            return response()->json(['error' => true, 'message' => trans('error_occurred')], 500);
        }
    }

    public function cleanupInfo(int $id)
    {
        try {
            // $year = $this->sessionYear->findById($id) ?? $this->sessionYear->findOnlyTrashedById($id);
            $year = SessionYear::withTrashed()->find($id);
            if (!$year) {
                return response()->json(['error' => true, 'message' => __('Session year not found.')], 404);
            }

            // Core Data checks (Students, Exams, Results, Fees)
            $studentsCount = Students::where('session_year_id', $id)->count();
            $examsCount = Exam::where('session_year_id', $id)->count();
            $resultsCount = ExamResult::where('session_year_id', $id)->count();
            $feesCount = Fee::where('session_year_id', $id)->count();

            $hasCoreData = ($studentsCount > 0 || $examsCount > 0 || $resultsCount > 0 || $feesCount > 0);

            // Deletable Data checks
            $attendanceCount = Attendance::where('session_year_id', $id)->count() + StaffAttendance::where('session_year_id', $id)->count();
            $assignmentsCount = Assignment::where('session_year_id', $id)->count();
            $onlineExamsCount = OnlineExam::where('session_year_id', $id)->count();
            $diaryCount = Diary::where('session_year_id', $id)->count();
            $communicationsCount = Notification::where('session_year_id', $id)->count() + Announcement::where('session_year_id', $id)->count();

            $deletableData = [
                'attendance' => $attendanceCount > 0,
                'assignments' => $assignmentsCount > 0,
                'online_exams' => $onlineExamsCount > 0,
                'diary' => $diaryCount > 0,
                'communications' => $communicationsCount > 0,
            ];

            return response()->json([
                'error' => false,
                'has_core_data' => $hasCoreData,
                'core_metrics' => [
                    'students' => $studentsCount > 0,
                    'offline_exams' => $examsCount > 0,
                    'final_results' => $resultsCount > 0,
                    'fees' => $feesCount > 0
                ],
                'deletable_data' => $deletableData,
                'session_year' => $year
            ]);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Session Year Controller -> cleanupInfo method");
            ResponseService::errorResponse();
        }
    }

    public function cleanupData(Request $request, int $id)
    {
        ResponseService::noPermissionThenSendJson('session-year-delete');

        // $request->validate([
        //     'modules' => 'required|array'
        // ]);

        try {
            DB::beginTransaction();

            $year = SessionYear::withTrashed()->find($id);
            if ($year && $year->default == 1) {
                ResponseService::errorResponse("Default Session Year cannot be deleted");
            }

            // if modules not send then delete session year and send success message
            if (!$request->has('modules')) {
                $year->forceDelete();
                $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.ALL_SESSION_YEARS"));
                $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.SETTINGS"));
                DB::commit();
                ResponseService::successResponse("Session year deleted successfully.");
            }

            $modules = $request->modules;

            if (in_array('attendance', $modules)) {
                Attendance::where('session_year_id', $id)->forceDelete();
                StaffAttendance::where('session_year_id', $id)->forceDelete();

                // TransportationAttendance in this table we don't have session_year_id, so please delete based on date range, session year start and end date and compare with date column in TransportationAttendance table
                TransportationAttendance::whereBetween('date', [$year->getRawOriginal('start_date'), $year->getRawOriginal('end_date')])->forceDelete();
            }

            if (in_array('assignments', $modules)) {
                Assignment::where('session_year_id', $id)->forceDelete();
            }

            if (in_array('online_exams', $modules)) {

                OnlineExamStudentAnswer::whereHas('online_exam', function ($query) use ($id) {
                    $query->where('session_year_id', $id);
                })->forceDelete();

                OnlineExamQuestionChoice::whereHas('online_exam', function ($query) use ($id) {
                    $query->where('session_year_id', $id);
                })->forceDelete();


                OnlineExam::where('session_year_id', $id)->forceDelete();
            }

            if (in_array('diary', $modules)) {
                Diary::where('session_year_id', $id)->forceDelete();
            }

            if (in_array('communications', $modules)) {
                Notification::where('session_year_id', $id)->forceDelete();
                Announcement::where('session_year_id', $id)->forceDelete();
            }

            // Note: We deliberately do NOT forceDelete the $year here. It remains in the Trash for historical reference.

            DB::commit();
            $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.ALL_SESSION_YEARS"));
            $this->cache->removeSchoolCache(config("constants.CACHE.SCHOOL.SETTINGS"));

            ResponseService::successResponse("Data cleanup completed successfully.");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Session Year Controller -> cleanupData method");
            ResponseService::errorResponse();
        }
    }
}
