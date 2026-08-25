<?php

namespace App\Http\Controllers;

use App\Models\PromoteStudent;
use App\Models\Students;
use App\Models\StudentSubject;
use App\Models\User;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\PromoteStudent\PromoteStudentInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\StudentSubject\StudentSubjectInterface;
use App\Repositories\User\UserInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class PromoteStudentController extends Controller
{

    private ClassSectionInterface $classSection;
    private SessionYearInterface $sessionYear;
    private StudentInterface $student;
    private UserInterface $user;
    private PromoteStudentInterface $promoteStudent;
    private CachingService $cache;
    private StudentSubjectInterface $studentSubject;

    public function __construct(ClassSectionInterface $classSection, SessionYearInterface $sessionYear, StudentInterface $student, UserInterface $user, PromoteStudentInterface $promoteStudent, CachingService $cachingService, StudentSubjectInterface $studentSubject)
    {
        $this->classSection = $classSection;
        $this->sessionYear = $sessionYear;
        $this->student = $student;
        $this->user = $user;
        $this->promoteStudent = $promoteStudent;
        $this->cache = $cachingService;
        $this->studentSubject = $studentSubject;
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['promote-student-list', 'transfer-student-list']);
        $classSections = $this->classSection->all(['*'], ['class', 'section', 'medium', 'class.stream', 'class.shift']);
        $sessionYears = $this->sessionYear->builder()->select(['id', 'name', 'start_date'])->orderBy('start_date', 'asc')->get();
        return view('promote_student.index', compact('classSections', 'sessionYears'));
    }

    public function store(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['promote-student-create', 'promote-student-edit']);
        $request->validate([
            'class_section_id' => 'required',
            'promote_data' => 'required',
            'new_class_section_id' => [
                $request->is_final_year ? 'nullable' : 'required',
                $request->is_final_year ? '' : 'exists:class_sections,id',
                function ($attr, $value, $fail) use ($request) {
                    if ($request->is_final_year) return;
                    $oldClass = \App\Models\ClassSection::find($request->class_section_id)?->class_id;
                    $newClass = \App\Models\ClassSection::find($value)?->class_id;

                    if ($oldClass && $newClass && $oldClass === $newClass) {
                        $fail(__('Cannot promote student to the same class.'));
                    }
                },
            ],
        ], ['promote_data.required' => "No Student Data Found"]);

        if (empty($request->promote_data)) {
            ResponseService::errorResponse("Please select at least one student.");
        }
        try {
            DB::beginTransaction();

            $sessionYearId = $request->from_session_year_id; // Get Current Session Year
            $promoteStudentData = array();
            // Fetch current roll numbers for all students being promoted/transferred
            $studentUserIds = collect($request->promote_data)->pluck('student_id');
            $currentStudents = $this->student->builder()->whereIn('user_id', $studentUserIds)->get(['user_id', 'roll_number'])->keyBy('user_id');

            foreach ($request->promote_data as $key => $data) {
                $isFinalYear = $request->has('is_final_year') && $request->is_final_year;

                // If Final Year and Pass, force Leave
                if ($isFinalYear && $data['result'] == 1) {
                    $data['status'] = 0; // Leave
                }

                $promoteRecord = array(
                    'student_id' => $data['student_id'],
                    'session_year_id' => $sessionYearId,
                    'result' => $data['result'],
                    'status' => $data['status'],
                    'roll_number' => $currentStudents[$data['student_id']]->roll_number ?? null,
                );

                if ($data['result'] == 1) {
                    // IF Student Then Store New Class Section in Promote Data
                    $promoteRecord['class_section_id'] = $request->class_section_id;

                    if ($data['status'] == 1) {
                        // IF Student Continues then get students IDs
                        $passStudentsIds[] = $data['student_id'];
                    }
                } else {
                    // IF Students Fails then store Current Class Section in Promote Data
                    $promoteRecord['class_section_id'] = $request->class_section_id;

                    if ($data['status'] == 1) {
                        // IF Student Fails then get students IDs
                        $failStudentsIds[] = $data['student_id'];
                    }
                }

                // IF Student Leaves then get Student IDs
                if ($data['status'] == 0) {
                    $leftStudentSIds[] = $data['student_id'];
                }

                if ($isFinalYear && $data['status'] == 0) {
                    // Graduated/Passed out finale: No target class/session
                    $targetClassSectionId = null;
                    $targetSessionYearId = null;
                } else if ($isFinalYear) {
                    // Final year + Fail + Continue
                    $targetClassSectionId = $request->class_section_id;
                    $targetSessionYearId = $request->session_year_id;
                } else {
                    // Normal promotion
                    $targetClassSectionId = $data['result'] == 1 ? $request->new_class_section_id : $request->class_section_id;
                    $targetSessionYearId = $request->session_year_id;
                }

                $promoteRecord['current_class_section_id'] = $targetClassSectionId;
                $promoteRecord['current_session_year_id'] = $targetSessionYearId;

                $promoteStudentData[] = $promoteRecord;
            }
            if (!empty($passStudentsIds)) {

                // Get Sort Value and Order Value from Settings
                $sortBy = !empty($this->cache->getSchoolSettings('roll_number_sort_column')) ? $this->cache->getSchoolSettings('roll_number_sort_column') : 'first_name';
                $orderBy = !empty($this->cache->getSchoolSettings('roll_number_sort_order')) ? $this->cache->getSchoolSettings('roll_number_sort_order') : 'asc';

                // Get The Data of Users who is passed with Student Relation and make Array to Update Student Details
                $studentUsers = $this->user->builder()->role('Student')->whereIn('id', $passStudentsIds)->with('studentWithoutOwner')->orderBy('users.' . $sortBy, $orderBy)->get();
                $studentsData = array();
                foreach ($studentUsers as $key => $user) {
                    $studentsData[] = array(
                        'id' => $user->studentWithoutOwner->id,
                        'roll_number' => (int) $key + 1,
                        'class_section_id' => $request->new_class_section_id,
                        'session_year_id' => $request->session_year_id,
                    );
                }

                // Upsert Student Data
                $this->student->upsert($studentsData, ['id'], ['roll_number', 'class_section_id', 'session_year_id']);
            }

            if (!empty($failStudentsIds)) {
                Students::whereIn('user_id', $failStudentsIds)->update(array(
                    'session_year_id' => $request->session_year_id,
                ));
            }

            // Reactivate Continue Students
            $continueStudentUserIds = array_merge($passStudentsIds ?? [], $failStudentsIds ?? []);
            if (!empty($continueStudentUserIds)) {
                $this->user->builder()->withTrashed()->whereIn('id', $continueStudentUserIds)->update(['status' => 1, 'deleted_at' => null]);
            }

            if (!empty($leftStudentSIds)) {
                // If student leaves, deactivate user AND revert enrollment to the old session
                $this->user->builder()->whereIn('id', $leftStudentSIds)->update(['status' => 0, 'deleted_at' => now()]);
                $this->student->builder()->whereIn('user_id', $leftStudentSIds)->update([
                    'class_section_id' => $request->class_section_id,
                    'session_year_id' => $request->from_session_year_id,
                ]);
            }

            $this->promoteStudent->upsert($promoteStudentData, ['class_section_id', 'student_id', 'session_year_id'], ['status', 'result', 'roll_number', 'current_class_section_id', 'current_session_year_id']);

            DB::commit();
            ResponseService::successResponse("Data Updated Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getPromoteData(Request $request)
    {
        $response = PromoteStudent::where(['class_section_id' => $request->class_section_id])->get();
        return response()->json($response);
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenRedirect('promote-student-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');
        $from_session_year_id = $request->from_session_year_id;

        $class_section_id = $request->class_section_id;
        $promotion_status = $request->promotion_status;
        $sql = $this->student->model()->where('school_id', Auth::user()->school_id)->where(function ($q) use ($class_section_id, $from_session_year_id, $promotion_status) {
            if ($promotion_status === '1') {
                // Promoted Only
                $q->whereHas('promote_student', function ($sq) use ($from_session_year_id, $class_section_id) {
                    $sq->where('class_section_id', $class_section_id)->where('session_year_id', $from_session_year_id);
                });
            } elseif ($promotion_status === '0') {
                // Pending Only
                $q->where(['class_section_id' => $class_section_id, 'session_year_id' => $from_session_year_id])
                    ->whereDoesntHave('promote_student', function ($sq) use ($from_session_year_id, $class_section_id) {
                        $sq->where('class_section_id', $class_section_id)->where('session_year_id', $from_session_year_id);
                    });
            } else {
                // All
                $q->where(['class_section_id' => $class_section_id, 'session_year_id' => $from_session_year_id])->orWhereHas('promote_student', function ($sq) use ($from_session_year_id, $class_section_id) {
                    $sq->where('class_section_id', $class_section_id)->where('session_year_id', $from_session_year_id);
                });
            }
        })->with(['user', 'promote_student' => function ($q) use ($class_section_id, $from_session_year_id) {
            $q->where(['class_section_id' => $class_section_id, 'session_year_id' => $from_session_year_id])->with('current_class_section.class.stream', 'current_class_section.section', 'current_class_section.medium');
        }])
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'");
                        });
                });
            });
        $total = $sql->count();
        // $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $sql->orderBy($sort, $order);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $offset + $no++;
            $tempRow['promotion_status'] = $row->promote_student ? 1 : 0;
            $tempRow['result'] = $row->promote_student->result ?? '';
            $tempRow['status'] = $row->promote_student->status ?? '';
            $tempRow['promoted_to'] = ($row->promote_student && $row->promote_student->current_class_section) ? $row->promote_student->current_class_section->full_name : '-';
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        // Calculate School Overall Progress based strictly on session year
        $school_promoted = $this->student->model()->where('school_id', Auth::user()->school_id)
            ->whereHas('promote_student', function ($sq) use ($from_session_year_id) {
                $sq->where('session_year_id', $from_session_year_id);
            })->count();

        $school_pending = $this->student->model()->where('school_id', Auth::user()->school_id)
            ->where('session_year_id', $from_session_year_id)
            ->whereDoesntHave('promote_student', function ($sq) use ($from_session_year_id) {
                $sq->where('session_year_id', $from_session_year_id);
            })->count();

        $bulkData['school_total'] = $school_promoted + $school_pending;
        $bulkData['school_promoted'] = $school_promoted;
        $bulkData['school_pending'] = $school_pending;

        return response()->json($bulkData);
    }

    public function transferStudentIndex()
    {
        ResponseService::noPermissionThenRedirect('transfer-student-create');

        $classSections = $this->classSection->all(['*'], ['class', 'section', 'medium', 'class.stream', 'class.shift']);
        $sessionYears = $this->sessionYear->builder()->select(['id', 'name', 'start_date'])->orderBy('start_date', 'asc')->get();
        $defaultSessionYear = $this->cache->getDefaultSessionYear();
        return view('promote_student.transfer', compact('classSections', 'sessionYears', 'defaultSessionYear'));
    }

    public function showTransferStudent(Request $request)
    {
        // ResponseService::noFeatureThenRedirect('Academics Management');
        ResponseService::noPermissionThenRedirect('transfer-student-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

        $class_section_id = $request->current_class_section;
        // $sessionYear = $request->transfer_session_year_id; // Get Current Session Year
        $defaultSessionYear = $this->cache->getDefaultSessionYear();
        $sessionYear = $defaultSessionYear->id;
        $sql = Students::where(['class_section_id' => $class_section_id, 'session_year_id' => $sessionYear])->whereHas('user', function ($query) {
            $query->where('status', 1);
        })->with('user')
            ->where(function ($q) use ($search) {
                $q->when($search, function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'");
                        });
                });
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
            $tempRow['no'] = $offset + $no++;
            $tempRow['student_id'] = $row->id;
            $tempRow['user_id'] = $row->user_id;
            $tempRow['name'] = $row->full_name;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function storeTransferStudent(Request $request)
    {
        // ResponseService::noFeatureThenRedirect('Academics Management');
        ResponseService::noAnyPermissionThenSendJson(['transfer-student-list', 'transfer-student-edit']);
        $request->validate([
            'current_class_section_id' => 'required',
            'new_class_section_id' => 'required',
            'student_ids' => 'required'
        ]);
        try {
            DB::beginTransaction();
            // $studentIds = json_decode($request->student_ids);
            $studentIds = explode(",", $request->student_ids);
            $roll_number_db = $this->student->builder()->select(DB::raw('max(roll_number)'))->where('class_section_id', $request->new_class_section_id)->first();
            $roll_number_db = $roll_number_db['max(roll_number)'];

            $updateStudent = array();
            foreach ($studentIds as $id) {
                $updateStudent[] = array(
                    'id' => $id,
                    'class_section_id' => $request->new_class_section_id,
                    'roll_number' => (int) $roll_number_db + 1,
                    // 'session_year_id' => $request->transfer_session_year_id,
                );
            }

            // foreach ($updateStudent as $student) {
            //     $user = $this->student->builder()->where('id', $student['id'])->with('user')->first();
            //     $studentSubject = $this->studentSubject->builder()->where('student_id', $user->user_id)->get();
            //     if ($studentSubject->count() > 0) {
            //         foreach ($studentSubject as $subject) {
            //             $subject->delete();
            //         }
            //     }
            // }

            $user = User::whereHas('student', function ($q) use ($studentIds) {
                $q->whereIn('id', $studentIds);
            })->get()->pluck('id');

            StudentSubject::whereIn('student_id', $user)->delete();

            // $studentSubject = $this->studentSubject->builder()->whereIn('student_id', $user)->get();
            // if ($studentSubject->count() > 0) {
            //     foreach ($studentSubject as $subject) {
            //         $subject->delete();
            //     }
            // }




            // $promoteStudentData = $this->promoteStudent->builder()->where('current_session_year_id', $request->transfer_session_year_id)->where('current_class_section_id', $request->current_class_section_id)->get();

            // if ($promoteStudentData) {
            //     foreach ($promoteStudentData as $data) {
            //         $data->current_class_section_id = $request->new_class_section_id;
            //         $data->save();
            //     }
            // }

            Students::upsert($updateStudent, ['id'], ['class_section_id', 'roll_number']);

            // $this->student->upsert($updateStudent, ['id'], ['class_section_id', 'roll_number']);
            DB::commit();
            ResponseService::successResponse("Data Updated Successfully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }
}
