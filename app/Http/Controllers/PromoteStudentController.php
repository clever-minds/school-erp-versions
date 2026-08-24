<?php

namespace App\Http\Controllers;

use App\Models\PromoteStudent;
use App\Models\School;
use App\Models\ClassSection;
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

class PromoteStudentController extends Controller {

    private ClassSectionInterface $classSection;
    private SessionYearInterface $sessionYear;
    private StudentInterface $student;
    private UserInterface $user;
    private PromoteStudentInterface $promoteStudent;
    private CachingService $cache;
    private StudentSubjectInterface $studentSubject;

    public function __construct(ClassSectionInterface $classSection, SessionYearInterface $sessionYear, StudentInterface $student, UserInterface $user, PromoteStudentInterface $promoteStudent, CachingService $cachingService, StudentSubjectInterface $studentSubject) {
        $this->classSection = $classSection;
        $this->sessionYear = $sessionYear;
        $this->student = $student;
        $this->user = $user;
        $this->promoteStudent = $promoteStudent;
        $this->cache = $cachingService;
        $this->studentSubject = $studentSubject;
    }

    public function index() {
        ResponseService::noAnyPermissionThenRedirect(['promote-student-list','transfer-student-list']);
        $classSections = $this->classSection->all(['*'], ['class', 'section', 'medium','class.stream']);
        $sessionYears = $this->sessionYear->builder()->select(['id', 'name'])->where('default', 0)->get();
        $schools = School::on('mysql')->select('id', 'name')->get();
        return view('promote_student.index', compact('classSections', 'sessionYears', 'schools'));
    }

    public function store(Request $request) {
        ResponseService::noAnyPermissionThenSendJson(['promote-student-create', 'promote-student-edit']);
        $request->validate([
            'class_section_id' => 'required',
            'promote_data' => 'required',
            'new_school_id' => 'nullable'
        ], ['promote_data.required' => "No Student Data Found"]);
        try {
            DB::beginTransaction();

            $promoteStudentData = array();
            foreach ($request->promote_data as $key => $data) {
                $promoteStudentData[$key] = array(
                    'student_id'      => $data['student_id'],
                    'session_year_id' => $request->session_year_id,
                    'result'          => $data['result'],
                    'status'          => $data['status'],
                    'school_id'       => $request->new_school_id ?? Auth::user()->school_id
                );

                if ($data['result'] == 1) {
                    // IF Student Then Store New Class Section in Promote Data
                    $promoteStudentData[$key]['class_section_id'] = $request->new_class_section_id;

                    if ($data['status'] == 1) {
                        // IF Student Continues then get students IDs
                        $passStudentsIds[] = $data['student_id'];
                    }
                } else {
                    // IF Students Fails then store Current Class Section in Promote Data
                    $promoteStudentData[$key]['class_section_id'] = $request->class_section_id;

                    if ($data['status'] == 1) {
                        // IF Student Fails then get students IDs
                        $failStudentsIds[] = $data['student_id'];
                    }
                }

                // IF Student Leaves then get Student IDs
                if ($data['status'] == 0) {
                    $leftStudentSIds[] = $data['student_id'];
                }

                $promoteStudentData[$key]['current_class_section_id'] = $request->new_class_section_id;
                $promoteStudentData[$key]['current_session_year_id'] = $request->session_year_id;
            }
            if (!empty($passStudentsIds)) {

                // Get Sort Value and Order Value from Settings
                $sortBy = !empty($this->cache->getSchoolSettings('roll_number_sort_column')) ? $this->cache->getSchoolSettings('roll_number_sort_column') : 'first_name';
                $orderBy = !empty($this->cache->getSchoolSettings('roll_number_sort_order')) ? $this->cache->getSchoolSettings('roll_number_sort_order') : 'asc';

                // Get The Data of Users who is passed with Student Relation and make Array to Update Student Details
                $studentUsers = $this->user->builder()->role('Student')->whereIn('id',$passStudentsIds)->with('student', 'student.guardian')->orderBy('users.'.$sortBy, $orderBy)->get();
                $guardianMap = []; // Map source_guardian_id -> target_guardian_id

                foreach ($studentUsers as $key => $user) {
                    if ($request->new_school_id && $request->new_school_id != Auth::user()->school_id) {
                        // Cross-database promotion
                        $targetSchool = School::on('mysql')->find($request->new_school_id);
                        if ($targetSchool && $targetSchool->database_name) {
                            $sourceDb = Config::get('database.connections.school.database');
                            $targetDb = $targetSchool->database_name;

                            // 1. Prepare student user data (explicitly)
                            $userData = [
                                'first_name' => $user->first_name,
                                'middle_name' => $user->middle_name,
                                'last_name' => $user->last_name,
                                'mother_name' => $user->mother_name,
                                'mobile' => $user->mobile,
                                'email' => $user->email,
                                'password' => $user->password,
                                'gender' => $user->gender,
                                'dob' => $user->getRawOriginal('dob'),
                                'current_address' => $user->current_address,
                                'permanent_address' => $user->permanent_address,
                                'school_id' => $request->new_school_id,
                                'status' => 1,
                            ];

                            $sourceStudent = $user->student;
                            $studentData = [
                                'admission_no' => $sourceStudent->admission_no,
                                'roll_number' => (int)$key + 1,
                                'class_section_id' => $request->new_class_section_id,
                                'school_id' => $request->new_school_id,
                                'admission_date' => $sourceStudent->getRawOriginal('admission_date'),
                                'uni_no' => $sourceStudent->uni_no ?? '',
                                'pen_no' => $sourceStudent->pen_no ?? '',
                                'cast' => $sourceStudent->cast ?? 'GENERAL',
                                'blood_group' => $sourceStudent->blood_group,
                                'nationality' => $sourceStudent->nationality,
                                'birth_place' => $sourceStudent->birth_place,
                                'last_school' => $sourceStudent->last_school,
                                'last_cleared_class' => $sourceStudent->last_cleared_class,
                                'education_board' => $sourceStudent->education_board,
                                'remarks' => $sourceStudent->remarks,
                            ];

                            // Get session year names from source
                            $sourceSessionYear = \App\Models\SessionYear::on('school')->find($request->session_year_id);
                            $sessionYearName = $sourceSessionYear ? $sourceSessionYear->name : null;
                            
                            $sourceJoinSessionYear = \App\Models\SessionYear::on('school')->find($sourceStudent->join_session_year_id);
                            $joinSessionYearName = $sourceJoinSessionYear ? $sourceJoinSessionYear->name : null;

                            // 2. Switch connection
                            Config::set('database.connections.school.database', $targetDb);
                            DB::purge('school');
                            DB::connection('school')->reconnect();

                            // Map session years by name in target database
                            if ($sessionYearName) {
                                $targetSessionYear = \App\Models\SessionYear::on('school')->where('name', $sessionYearName)->first();
                                if ($targetSessionYear) {
                                    $studentData['session_year_id'] = $targetSessionYear->id;
                                } else {
                                    $studentData['session_year_id'] = \App\Models\SessionYear::on('school')->where('default', 1)->first()->id ?? 1;
                                }
                            }

                            if ($joinSessionYearName) {
                                $targetJoinSessionYear = \App\Models\SessionYear::on('school')->where('name', $joinSessionYearName)->first();
                                if ($targetJoinSessionYear) {
                                    $studentData['join_session_year_id'] = $targetJoinSessionYear->id;
                                } else {
                                    $studentData['join_session_year_id'] = $studentData['session_year_id'];
                                }
                            }

                            // 3. Handle User
                            $existingUser = null;
                            if (!empty($userData['email'])) {
                                $existingUser = \App\Models\User::on('school')->role('Student')->where('email', $userData['email'])->first();
                            } elseif (!empty($userData['mobile'])) {
                                $existingUser = \App\Models\User::on('school')->role('Student')->where('mobile', $userData['mobile'])->first();
                            }

                            if (!$existingUser) {
                                $newUser = \App\Models\User::on('school')->create($userData);
                                $newUser->assignRole('student');
                            } else {
                                $newUser = $existingUser;
                                $newUser->update($userData); // Sync existing user details
                            }

                            // 4. Handling Guardian
                            $sourceGuardian = $sourceStudent->guardian;
                            $targetGuardianId = 0;

                            if ($sourceGuardian) {
                                // Check if we already processed this guardian in this request
                                if (isset($guardianMap[$sourceStudent->guardian_id])) {
                                    $targetGuardianId = $guardianMap[$sourceStudent->guardian_id];
                                } else {
                                    $guardianData = [
                                        'first_name' => $sourceGuardian->first_name,
                                        'middle_name' => $sourceGuardian->middle_name,
                                        'last_name' => $sourceGuardian->last_name,
                                        'mobile' => $sourceGuardian->mobile,
                                        'email' => $sourceGuardian->email,
                                        'password' => $sourceGuardian->password,
                                        'gender' => $sourceGuardian->gender,
                                        'dob' => $sourceGuardian->getRawOriginal('dob'),
                                        'current_address' => $sourceGuardian->current_address,
                                        'permanent_address' => $sourceGuardian->permanent_address,
                                        'school_id' => $request->new_school_id,
                                        'status' => 1,
                                    ];

                                    $existingGuardian = null;
                                    if (!empty($guardianData['email'])) {
                                        $existingGuardian = \App\Models\User::on('school')->role('Guardian')->where('email', $guardianData['email'])->first();
                                    } elseif (!empty($guardianData['mobile'])) {
                                        $existingGuardian = \App\Models\User::on('school')->role('Guardian')->where('mobile', $guardianData['mobile'])->first();
                                    }

                                    if (!$existingGuardian) {
                                        $newGuardian = \App\Models\User::on('school')->create($guardianData);
                                        $newGuardian->assignRole('guardian');
                                        $targetGuardianId = $newGuardian->id;
                                    } else {
                                        $targetGuardianId = $existingGuardian->id;
                                        $existingGuardian->update($guardianData); // Sync existing guardian details
                                    }
                                    // Save to map for next student
                                    $guardianMap[$sourceStudent->guardian_id] = $targetGuardianId;
                                }
                            }
                            
                            $studentData['guardian_id'] = $targetGuardianId;
                            $studentData['user_id'] = $newUser->id;
                            $targetStudent = \App\Models\Students::on('school')->create($studentData);

                            // 5. Transfer Extra User Data (Custom Fields)
                            $sourceExtraData = \App\Models\ExtraStudentData::where('user_id', $user->id)->get();
                            if ($sourceExtraData->count() > 0) {
                                foreach ($sourceExtraData as $extra) {
                                    $sourceField = \App\Models\FormField::find($extra->form_field_id);
                                    if ($sourceField) {
                                        $targetField = \App\Models\FormField::on('school')->where('name', $sourceField->name)->first();
                                        if ($targetField) {
                                            \App\Models\ExtraStudentData::on('school')->updateOrCreate(
                                                ['user_id' => $newUser->id, 'form_field_id' => $targetField->id],
                                                ['data' => $extra->data, 'school_id' => $request->new_school_id]
                                            );
                                        }
                                    }
                                }
                            }

                            // 6. Switch back
                            Config::set('database.connections.school.database', $sourceDb);
                            DB::purge('school');
                            DB::connection('school')->reconnect();

                            // 6. Deactivate in source
                            $user->update(['status' => 0]);
                        }
                    } else {
                        $studentsData[] = array(
                            'id' => $user->student->id,
                            'roll_number' => (int)$key + 1,
                            'class_section_id' => $request->new_class_section_id,
                            'session_year_id'  => $request->session_year_id,
                            'school_id'        => $request->new_school_id ?? $user->student->school_id,
                        );
                    }
                }

                // Upsert Student Data
                if (!empty($studentsData)) {
                    $this->student->upsert($studentsData,['id'],['roll_number','class_section_id','session_year_id', 'school_id']);
                }
            }

            if (!empty($failStudentsIds)) {
                $this->student->builder()->whereIn('user_id', $failStudentsIds)->update(array(
                    'session_year_id' => $request->session_year_id,
                ));
            }

            if (!empty($leftStudentSIds)) {
                $this->user->builder()->whereIn('id', $leftStudentSIds)->update(['status' => 0,'deleted_at' => now()]);
            }
            $this->promoteStudent->upsert($promoteStudentData, ['class_section', 'student_id', 'session_year_id'], ['status', 'result']);
            DB::commit();
            ResponseService::successResponse("Data Updated Successfully");

        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getPromoteData(Request $request) {
        $response = PromoteStudent::where(['class_section_id' => $request->class_section_id])->get();
        return response()->json($response);
    }

    public function getClassSectionBySchool($school_id)
    {
        try {
            $school = School::on('mysql')->find($school_id);
            if (!$school) {
                return ResponseService::errorResponse("School not found");
            }

            // Get current database name to restore it later
            $current_db = Config::get('database.connections.school.database');

            // Switch to target school's database
            Config::set('database.connections.school.database', $school->database_name);
            DB::purge('school');
            DB::connection('school')->reconnect();
            
            // Fetch class sections from the other school's database
            $class_sections = ClassSection::on('school')->withoutGlobalScopes()
                ->where('school_id', $school_id)
                ->with('class', 'class.stream', 'section', 'medium')
                ->get();

            // Restore original database connection
            Config::set('database.connections.school.database', $current_db);
            DB::purge('school');
            DB::connection('school')->reconnect();

            ResponseService::successResponse('Data Fetched Successfully', $class_sections);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "PromoteStudent Controller -> getClassSectionBySchool method");
            ResponseService::errorResponse();
        }
    }

    public function show(Request $request) {
        ResponseService::noPermissionThenRedirect('promote-student-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

        $class_section_id = $request->class_section_id;
        $sessionYear = $this->cache->getDefaultSessionYear(); // Get Current Session Year
        $sql = $this->student->builder()->where(['class_section_id' => $class_section_id, 'session_year_id' => $sessionYear->id])->whereHas('user', function ($query) {
            $query->where('status', 1);
        })->with('user')
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                ->orWhereHas('user',function($q) use($search){
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
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function showTransferStudent(Request $request) {
        // ResponseService::noFeatureThenRedirect('Academics Management');
        ResponseService::noPermissionThenRedirect('transfer-student-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

        $class_section_id = $request->current_class_section;
        $sessionYear = $this->cache->getDefaultSessionYear(); // Get Current Session Year
        $sql = $this->student->builder()->where(['class_section_id' => $class_section_id, 'session_year_id' => $sessionYear->id])->whereHas('user', function ($query) {
            $query->where('status', 1);
        })->with('user')
        ->where(function($q) use($search) {
            $q->when($search, function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                ->orWhereHas('user',function($q) use($search){
                    $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'");
                });
            });
        });
            
        $total = $sql->count();
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

    public function storeTransferStudent(Request $request){
        // ResponseService::noFeatureThenRedirect('Academics Management');
        ResponseService::noAnyPermissionThenSendJson(['transfer-student-list', 'transfer-student-edit']);
        $request->validate([
            'current_class_section_id' => 'required',
            'new_class_section_id' => 'required',
            'student_ids' => 'required',
            'new_school_id' => 'nullable'
        ]);
        try {
            DB::beginTransaction();
            // $studentIds = json_decode($request->student_ids);
            $studentIds = explode(",",$request->student_ids);
            $roll_number_db = $this->student->builder()->select(DB::raw('max(roll_number)'))->where('class_section_id', $request->new_class_section_id)->first();
            $roll_number_db = $roll_number_db['max(roll_number)'];

            $updateStudent = array();
            $guardianMap = []; // Map source_guardian_id -> target_guardian_id

            foreach ($studentIds as $id) {
                $sourceStudent = $this->student->builder()->where('id', $id)->with('user', 'guardian')->first();
                
                if ($request->new_school_id && $request->new_school_id != Auth::user()->school_id) {
                    // Cross-database transfer
                    $targetSchool = School::on('mysql')->find($request->new_school_id);
                    if ($targetSchool && $targetSchool->database_name) {
                        $sourceDb = Config::get('database.connections.school.database');
                        $targetDb = $targetSchool->database_name;

                        // 1. Prepare student user data (explicitly)
                        $userData = [
                            'first_name' => $sourceStudent->user->first_name,
                            'middle_name' => $sourceStudent->user->middle_name,
                            'last_name' => $sourceStudent->user->last_name,
                            'mother_name' => $sourceStudent->user->mother_name,
                            'mobile' => $sourceStudent->user->mobile,
                            'email' => $sourceStudent->user->email,
                            'password' => $sourceStudent->user->password,
                            'gender' => $sourceStudent->user->gender,
                            'dob' => $sourceStudent->user->getRawOriginal('dob'),
                            'current_address' => $sourceStudent->user->current_address,
                            'permanent_address' => $sourceStudent->user->permanent_address,
                            'school_id' => $request->new_school_id,
                            'status' => 1,
                        ];

                        $studentData = [
                            'admission_no' => $sourceStudent->admission_no,
                            'roll_number' => (int)$roll_number_db + 1,
                            'class_section_id' => $request->new_class_section_id,
                            'school_id' => $request->new_school_id,
                            'admission_date' => $sourceStudent->getRawOriginal('admission_date'),
                            'uni_no' => $sourceStudent->uni_no ?? '',
                            'pen_no' => $sourceStudent->pen_no ?? '',
                            'cast' => $sourceStudent->cast ?? 'GENERAL',
                            'blood_group' => $sourceStudent->blood_group,
                            'nationality' => $sourceStudent->nationality,
                            'birth_place' => $sourceStudent->birth_place,
                            'last_school' => $sourceStudent->last_school,
                            'last_cleared_class' => $sourceStudent->last_cleared_class,
                            'education_board' => $sourceStudent->education_board,
                            'remarks' => $sourceStudent->remarks,
                        ];
                        $roll_number_db++;

                        // Get session year names from source
                        $sourceSessionYear = \App\Models\SessionYear::on('school')->find($sourceStudent->session_year_id);
                        $sessionYearName = $sourceSessionYear ? $sourceSessionYear->name : null;
                        
                        $sourceJoinSessionYear = \App\Models\SessionYear::on('school')->find($sourceStudent->join_session_year_id);
                        $joinSessionYearName = $sourceJoinSessionYear ? $sourceJoinSessionYear->name : null;

                        // 2. Switch to target database
                        Config::set('database.connections.school.database', $targetDb);
                        DB::purge('school');
                        DB::connection('school')->reconnect();

                        // Map session years by name in target database
                        if ($sessionYearName) {
                            $targetSessionYear = \App\Models\SessionYear::on('school')->where('name', $sessionYearName)->first();
                            if ($targetSessionYear) {
                                $studentData['session_year_id'] = $targetSessionYear->id;
                            } else {
                                $studentData['session_year_id'] = \App\Models\SessionYear::on('school')->where('default', 1)->first()->id ?? 1;
                            }
                        }

                        if ($joinSessionYearName) {
                            $targetJoinSessionYear = \App\Models\SessionYear::on('school')->where('name', $joinSessionYearName)->first();
                            if ($targetJoinSessionYear) {
                                $studentData['join_session_year_id'] = $targetJoinSessionYear->id;
                            } else {
                                $studentData['join_session_year_id'] = $studentData['session_year_id'];
                            }
                        }

                        // 3. Handle User
                        $existingUser = null;
                        if (!empty($userData['email'])) {
                            $existingUser = \App\Models\User::on('school')->role('Student')->where('email', $userData['email'])->first();
                        } elseif (!empty($userData['mobile'])) {
                            $existingUser = \App\Models\User::on('school')->role('Student')->where('mobile', $userData['mobile'])->first();
                        }

                        if (!$existingUser) {
                            $newUser = \App\Models\User::on('school')->create($userData);
                            $newUser->assignRole('student');
                        } else {
                            $newUser = $existingUser;
                            $newUser->update($userData); // Sync existing user
                        }

                        // 4. Handling Guardian
                        $sourceGuardian = $sourceStudent->guardian;
                        $targetGuardianId = 0;

                        if ($sourceGuardian) {
                            // Check map for siblings
                            if (isset($guardianMap[$sourceStudent->guardian_id])) {
                                $targetGuardianId = $guardianMap[$sourceStudent->guardian_id];
                            } else {
                                $guardianData = [
                                    'first_name' => $sourceGuardian->first_name,
                                    'middle_name' => $sourceGuardian->middle_name,
                                    'last_name' => $sourceGuardian->last_name,
                                    'mobile' => $sourceGuardian->mobile,
                                    'email' => $sourceGuardian->email,
                                    'password' => $sourceGuardian->password,
                                    'gender' => $sourceGuardian->gender,
                                    'dob' => $sourceGuardian->getRawOriginal('dob'),
                                    'current_address' => $sourceGuardian->current_address,
                                    'permanent_address' => $sourceGuardian->permanent_address,
                                    'school_id' => $request->new_school_id,
                                    'status' => 1,
                                ];

                                $existingGuardian = null;
                                if (!empty($guardianData['email'])) {
                                    $existingGuardian = \App\Models\User::on('school')->role('Guardian')->where('email', $guardianData['email'])->first();
                                } elseif (!empty($guardianData['mobile'])) {
                                    $existingGuardian = \App\Models\User::on('school')->role('Guardian')->where('mobile', $guardianData['mobile'])->first();
                                }

                                if (!$existingGuardian) {
                                    $newGuardian = \App\Models\User::on('school')->create($guardianData);
                                    $newGuardian->assignRole('guardian');
                                    $targetGuardianId = $newGuardian->id;
                                } else {
                                    $targetGuardianId = $existingGuardian->id;
                                    $existingGuardian->update($guardianData); // Sync existing guardian
                                }
                                $guardianMap[$sourceStudent->guardian_id] = $targetGuardianId;
                            }
                        }
                        
                        $studentData['guardian_id'] = $targetGuardianId;
                        $studentData['user_id'] = $newUser->id;
                        $targetStudent = \App\Models\Students::on('school')->create($studentData);

                        // 5. Transfer Extra User Data (Custom Fields)
                        $sourceExtraData = \App\Models\ExtraStudentData::where('user_id', $sourceStudent->user_id)->get();
                        if ($sourceExtraData->count() > 0) {
                            foreach ($sourceExtraData as $extra) {
                                $sourceField = \App\Models\FormField::find($extra->form_field_id);
                                if ($sourceField) {
                                    $targetField = \App\Models\FormField::on('school')->where('name', $sourceField->name)->first();
                                    if ($targetField) {
                                        \App\Models\ExtraStudentData::on('school')->updateOrCreate(
                                            ['user_id' => $newUser->id, 'form_field_id' => $targetField->id],
                                            ['data' => $extra->data, 'school_id' => $request->new_school_id]
                                        );
                                    }
                                }
                            }
                        }

                        // 4. Switch back to source database
                        Config::set('database.connections.school.database', $sourceDb);
                        DB::purge('school');
                        DB::connection('school')->reconnect();

                        // 5. Deactivate in source
                        $sourceStudent->user->update(['status' => 0]);
                    }
                } else {
                    // Same database transfer
                    $updateStudent[] = array(
                        'id' => $id,
                        'class_section_id' => $request->new_class_section_id,
                        'roll_number' => (int)$roll_number_db + 1,
                        'school_id' => $request->new_school_id ?? Auth::user()->school_id
                    );
                    $roll_number_db++;
                }
            }

            if (!empty($updateStudent)) {
                foreach($updateStudent as $student){
                    $user = $this->student->builder()->where('id', $student['id'])->with('user')->first();
                    $studentSubject = $this->studentSubject->builder()->where('student_id', $user->user_id)->get();
                    if($studentSubject->count() > 0){
                        foreach($studentSubject as $subject){
                            $subject->delete();
                        }
                    }
                }
                $this->student->upsert($updateStudent,['id'],['class_section_id','roll_number', 'school_id']);
            }

            DB::commit();
            ResponseService::successResponse("Data Updated Successfully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse($e->getMessage());
        }
    }
}
