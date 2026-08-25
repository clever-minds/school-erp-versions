<?php

namespace App\Http\Controllers\Api;

use Throwable;
use Stripe\StripeClient;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\CachingService;
use JetBrains\PhpStorm\NoReturn;
use App\Services\FeaturesService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserDataResource;
use App\Repositories\Exam\ExamInterface;
use App\Repositories\Fees\FeesInterface;
use App\Repositories\User\UserInterface;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\TimetableCollection;
use App\Repositories\Topics\TopicsInterface;
use App\Repositories\Holiday\HolidayInterface;
use App\Repositories\Lessons\LessonsInterface;
use App\Repositories\Sliders\SlidersInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\FeesPaid\FeesPaidInterface;
use App\Repositories\Timetable\TimetableInterface;
use App\Repositories\Assignment\AssignmentInterface;
use App\Repositories\Attendance\AttendanceInterface;
use App\Repositories\ExamResult\ExamResultInterface;
use App\Repositories\OnlineExam\OnlineExamInterface;
use App\Repositories\OptionalFee\OptionalFeeInterface;
use App\Repositories\Announcement\AnnouncementInterface;
use App\Repositories\ClassSubject\ClassSubjectInterface;
use App\Repositories\CompulsoryFee\CompulsoryFeeInterface;
use App\Repositories\SubjectTeacher\SubjectTeacherInterface;
use App\Repositories\PaymentTransaction\PaymentTransactionInterface;
use App\Repositories\AssignmentSubmission\AssignmentSubmissionInterface;
use App\Repositories\PaymentConfiguration\PaymentConfigurationInterface;

class ParentApiController extends Controller {

    private StudentInterface $student;
    private UserInterface $user;
    private AssignmentInterface $assignment;
    private AssignmentSubmissionInterface $assignmentSubmission;
    private CachingService $cache;
    private TimetableInterface $timetable;
    private ExamInterface $exam;
    private ExamResultInterface $examResult;
    private LessonsInterface $lesson;
    private TopicsInterface $lessonTopic;
    private AttendanceInterface $attendance;
    private HolidayInterface $holiday;
    private SubjectTeacherInterface $subjectTeacher;
    private AnnouncementInterface $announcement;
    private OnlineExamInterface $onlineExam;
    private FeesInterface $fees;
    private PaymentTransactionInterface $paymentTransaction;
    private CompulsoryFeeInterface $compulsoryFee;
    private SlidersInterface $sliders;
    private PaymentConfigurationInterface $paymentConfigrations;
    private OptionalFeeInterface $optionalFees;
    private FeesPaidInterface $feesPaid;
    private SubjectTeacherInterface $subjectTeachers;

    public function __construct(StudentInterface $student, UserInterface $user, AssignmentInterface $assignment, AssignmentSubmissionInterface $assignmentSubmission, CachingService $cache, TimetableInterface $timetable, ExamInterface $exam, ExamResultInterface $examResult, LessonsInterface $lesson, TopicsInterface $lessonTopic, AttendanceInterface $attendance, HolidayInterface $holiday, SubjectTeacherInterface $subjectTeacher, AnnouncementInterface $announcement, OnlineExamInterface $onlineExam,ClassSubjectInterface $classSubject, FeesInterface $fees, PaymentTransactionInterface $paymentTransaction, CompulsoryFeeInterface $compulsoryFee, SlidersInterface $sliders, PaymentConfigurationInterface $paymentConfigrations, OptionalFeeInterface $optionalFees, FeesPaidInterface $feesPaid,FeaturesService $featuresService, SubjectTeacherInterface $subjectTeachers) {
        $this->student = $student;
        $this->user = $user;
        $this->assignment = $assignment;
        $this->assignmentSubmission = $assignmentSubmission;
        $this->cache = $cache;
        $this->timetable = $timetable;
        $this->exam = $exam;
        $this->examResult = $examResult;
        $this->lesson = $lesson;
        $this->lessonTopic = $lessonTopic;
        $this->attendance = $attendance;
        $this->holiday = $holiday;
        $this->subjectTeacher = $subjectTeacher;
        $this->announcement = $announcement;
        $this->onlineExam = $onlineExam;
        $this->classSubject = $classSubject;
        $this->fees = $fees;
        $this->paymentTransaction = $paymentTransaction;
        $this->compulsoryFee = $compulsoryFee;
        $this->sliders = $sliders;
        $this->paymentConfigrations = $paymentConfigrations;
        $this->optionalFees = $optionalFees;
        $this->feesPaid = $feesPaid;
        $this->featureService = $featuresService;
        $this->subjectTeachers = $subjectTeachers;
    }

    #[NoReturn] public function login(Request $request) {
        if (Auth::attempt([
            'email'    => $request->email,
            'password' => $request->password
        ])) {
            $auth = Auth::user()->load('child.user','child.class_section.class','child.class_section.section','child.class_section.medium','child.user.school');

            if (!$auth->hasRole('Guardian')) {
                ResponseService::errorResponse('Invalid Login Credentials', null, config('constants.RESPONSE_CODE.INVALID_LOGIN'));
            }

            if ($request->fcm_id) {
                $auth->fcm_id = $request->fcm_id;
                $auth->save();
            }

            $token = $auth->createToken($auth->first_name)->plainTextToken;

            $user = $auth;
            ResponseService::successResponse('User logged-in!', new UserDataResource($user), ['token' => $token], config('constants.RESPONSE_CODE.LOGIN_SUCCESS'));
        } else {
            ResponseService::errorResponse('Invalid Login Credentials', null, config('constants.RESPONSE_CODE.INVALID_LOGIN'));
        }
    }

    public function subjects(Request $request) {
        $validator = Validator::make($request->all(), ['child_id' => 'required|numeric',]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = Auth::user();
            $children = $user->guardianRelationChild()->where('id', $request->child_id)->first();
            $subjects = $children->currentSemesterSubjects();
            ResponseService::successResponse('Student Subject Fetched Successfully.', $subjects);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function classSubjects(Request $request) {
        $validator = Validator::make($request->all(), ['child_id' => 'required|numeric',]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = $request->user();
            $children = $user->guardianRelationChild()->where('id', $request->child_id)->first();
            $subjects = $children->currentSemesterClassSubjects();
            ResponseService::successResponse('Class Subject Fetched Successfully.', $subjects);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getTimetable(Request $request) {
        $validator = Validator::make($request->all(), ['child_id' => 'required|numeric',]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = $request->user();
            $children = $user->guardianRelationChild()->where('id', $request->child_id)->first();
            $timetable = $this->timetable->builder()->where('class_section_id', $children->class_section_id)->with('subject_teacher')->orderBy('day')->orderBy('start_time')->get();
            ResponseService::successResponse("Timetable Fetched Successfully", new TimetableCollection($timetable));
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /**
     * subject_id : 2
     * lesson_id : 1 //OPTIONAL
     */
    public function getLessons(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required|numeric',
            'lesson_id'         => 'nullable|numeric',
            'class_subject_id'  => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $user = $request->user();
            $children = $user->guardianRelationChild()->where('id', $request->child_id)->first();
            $data = $this->lesson->builder()->where(['class_section_id' => $children->class_section_id, 'class_subject_id' => $request->class_subject_id])->with('topic', 'file');
            if ($request->lesson_id) {
                $data->where('id', $request->lesson_id);
            }
            $data = $data->get();
            ResponseService::successResponse("Lessons Fetched Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /**
     * lesson_id : 1
     * topic_id : 1    //OPTIONAL
     */
    public function getLessonTopics(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'  => 'required|numeric',
            'lesson_id' => 'required|numeric',
            'topic_id'  => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $data = $this->lessonTopic->builder()->where('lesson_id', $request->lesson_id)->with('file');
            if ($request->topic_id) {
                $data->where('id', $request->topic_id);
            }
            $data = $data->get();
            ResponseService::successResponse("Topics Fetched Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /**
     * assignment_id : 1    //OPTIONAL
     * subject_id : 1       //OPTIONAL
     */
    public function getAssignments(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required|numeric',
            'assignment_id'     => 'nullable|numeric',
            'class_subject_id'  => 'nullable|numeric',
            'is_submitted'      => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = $request->user();
            $children = $user->guardianRelationChild()->where('id', $request->child_id)->first();
            $data = $this->assignment->builder()->where('class_section_id', $children->class_section_id)->with(['file', 'class_subject.subject', 'submission' => function($query) use($children){
                $query->where('student_id',$children->user_id)->with('file');
            }]);
            if ($request->assignment_id) {
                $data->where('id', $request->assignment_id);
            }
            if ($request->class_subject_id) {
                $data->where('class_subject_id', $request->class_subject_id);
            }
            if (isset($request->is_submitted)) {
                if ($request->is_submitted) {
                    $data->whereHas('submission', function ($q) use ($children) {
                        $q->where('student_id', $children->user_id);
                    });
                } else {
                    $data->whereDoesntHave('submission',function($q) use($children){
                        $q->where('student_id',$children->user_id);
                    });
                }
            }
            $data = $data->orderBy('id', 'desc')->paginate(15);
            ResponseService::successResponse("Assignments Fetched Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /**
     * month : 4 //OPTIONAL
     * year : 2022 //OPTIONAL
     */
    public function getAttendance(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'month'    => 'nullable|numeric',
            'year'     => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = $request->user();
            $children = $user->guardianRelationChild()->where('id', $request->child_id)->first();
            $sessionYear = $this->cache->getDefaultSessionYear($children->school_id);

            $attendance = $this->attendance->builder()->where(['student_id' => $children->user_id, 'session_year_id' => $sessionYear->id]);
            $holidays = $this->holiday->builder();
            if (isset($request->month)) {
                $attendance = $attendance->whereMonth('date', $request->month);
                $holidays = $holidays->whereMonth('date', $request->month);
            }

            if (isset($request->year)) {
                $attendance = $attendance->whereYear('date', $request->year);
                $holidays = $holidays->whereYear('date', $request->year);
            }
            $attendance = $attendance->get();
            $holidays = $holidays->get();

            $data = ['attendance' => $attendance, 'holidays' => $holidays, 'session_year' => $sessionYear];

            ResponseService::successResponse("Attendance Details Fetched Successfully",$data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getAnnouncements(Request $request) {
        $validator = Validator::make($request->all(), [
            'type'              => 'required|in:subject,class',
            'child_id'          => 'required_if:type,subject,class|numeric',
            'class_subject_id'  => 'required_if:type,subject|numeric'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = $request->user();
            $children = $user->guardianRelationChild()->where('id', $request->child_id)->first();
            $classSectionId = $children->class_section_id;

            $sessionYear = $this->cache->getDefaultSessionYear($children->school_id);
            if (isset($request->type) && $request->type == "subject") {
                $table = $this->subjectTeacher->builder()->where(['class_section_id' => $children->class_section_id, 'class_subject_id' => $request->class_subject_id])->get()->pluck('id');
                if ($table === null) {
                    ResponseService::errorResponse("Invalid Subject ID", null, config('constants.RESPONSE_CODE.INVALID_SUBJECT_ID'));
                }
            }
            $data = $this->announcement->builder()->with('file')->where('session_year_id', $sessionYear->id);

            if (isset($request->type) && $request->type == "class") {
                $data = $data->whereHas('announcement_class', function ($query) use ($classSectionId) {
                    $query->where(['class_section_id' => $classSectionId,'class_subject_id' => null]);
                });
            }


            if (isset($request->type) && $request->type == "subject") {
                $data = $data->whereHas('announcement_class', function ($query) use ($classSectionId, $request) {
                    $query->where(['class_section_id' => $classSectionId ,'class_subject_id' => $request->class_subject_id]);
                });
            }

            $data = $data->orderBy('id', 'desc')->paginate(15);
            ResponseService::successResponse("Announcement Details Fetched Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }


    public function getTeachers(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'  => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = Auth::user();
            $children = $user->guardianRelationChild()->where('id', $request->child_id)->first();
            $class_subject_id = $children->selectedStudentSubjects()->pluck('class_subject_id');
            $subjectTeachers = $this->subjectTeachers->builder()->select('id','subject_id','teacher_id','school_id')->whereIn('class_subject_id',$class_subject_id)->where('class_section_id',$children->class_section_id)->with('subject:id,name,type','teacher:id,first_name,last_name,image,mobile')->get();
            ResponseService::successResponse("Teacher Details Fetched Successfully", $subjectTeachers);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getSessionYear(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = $request->user();
            $children = $user->guardianRelationChild()->where('id', $request->child_id)->first();
            $sessionYear = $this->cache->getDefaultSessionYear($children->school_id);
            ResponseService::successResponse("Session Year Fetched Successfully", $sessionYear ?? []);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getChildProfileDetails(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = Auth::user();
            $childData = $user->guardianRelationChild()->where('id', $request->child_id)->with(['class_section' => function($subquery){
                $subquery->with('section','class','medium','class.shift','class.stream');
            },'guardian','user' => function($q){
                $q->with('extra_student_details.form_field','school');
            }])->first();
            // dd($childData->toArray());

            $data = array(
                'id'                => $childData->id,
                'first_name'        => $childData->user->first_name,
                'last_name'         => $childData->user->last_name,
                'mobile'            => $childData->user->mobile,
                'roll_number'       => $childData->roll_number,
                'admission_no'      => $childData->admission_no,
                'admission_date'    => $childData->admission_date,
                'gender'            => $childData->user->gender,
                'image'             => $childData->user->image,
                'dob'               => $childData->user->dob,
                'current_address'   => $childData->user->current_address,
                'permanent_address' => $childData->user->permanent_address,
                'occupation'        => $childData->user->occupation,
                'status'            => $childData->user->status,
                'fcm_id'            => $childData->user->fcm_id,
                'school_id'         => $childData->school_id,
                'session_year_id'   => $childData->session_year_id,
                'email_verified_at' => $childData->user->email_verified_at,
                'created_at'        => $childData->created_at,
                'updated_at'        => $childData->updated_at,
                'class_section'     => $childData->class_section,
                'guardian'          => $childData->guardian,
                'extra_details'     => $childData->user->extra_student_details,
                'school'            => $childData->user->school,
            );
            ResponseService::successResponse('Data Fetched Successfully', $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /** EXAM */

    public function getExamList(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'status'   => 'nullable:digits:0,1,2,3'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $this->student->findById($request->child_id, ['id', 'user_id', 'class_section_id'], ['class_section']);
            $classId = $student->class_section->class_id;
            $exam = $this->exam->builder()
                ->where('class_id', $classId)
                ->with([
                    'timetable' => function ($query) {
                        $query->selectRaw('* , SUM(total_marks) as total_marks')
                            ->groupBy('exam_id');
                    }
                ])->get();

            $exam_data = array();
            foreach ($exam as $data) {
                if (isset($request->status) && $request->status != $data->exam_status && $request->status != 3) {
                    continue;
                }

                $exam_data[] = [
                    'id'           => $data->id,
                    'name'         => $data->name,
                    'description'  => $data->description,
                    'publish'      => $data->publish,
                    'session_year' => $data->session_year->name,
                    'exam_starting_date' => $data->start_date,
                    'exam_ending_date' => $data->end_date,
                    'exam_status'  => $data->exam_status,
                ];
            }

            ResponseService::successResponse("Exam List fetched Successfully", $exam_data ?? []);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'parentApiController :- getExamList Method');
            ResponseService::errorResponse();
        }
    }

    public function getExamDetails(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'  => 'required|numeric',
            'exam_id'   => 'required|nullable'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $studentData = $this->student->findById($request->child_id, ['id', 'user_id', 'class_section_id'], ['class_section']);
            $classId = $studentData->class_section->class_id;
            $examData = $this->exam->builder()
                ->where([
                    'id' => $request->exam_id,
                    'class_id' => $classId
                ])
                ->with([
                    'timetable' => function ($query){
                        $query->owner()->with(['class_subject.subject'])->orderby('date');
                    }
            ])->first();


            if (!$examData) {
                ResponseService::successResponse("", []);
            }


            foreach ($examData->timetable as $data) {
                $exam_data[] = array(
                    'exam_timetable_id' => $data->id,
                    'total_marks'       => $data->total_marks,
                    'passing_marks'     => $data->passing_marks,
                    'date'              => $data->date,
                    'starting_time'     => $data->start_time,
                    'ending_time'       => $data->end_time,
                    'subject'           => array(
                        'id'                => $data->class_subject->subject->id,
                        'class_subject_id'  => $data->class_subject_id,
                        'name'              => $data->class_subject->subject->name,
                        'bg_color'          => $data->class_subject->subject->bg_color,
                        'image'             => $data->class_subject->subject->image,
                        'type'              => $data->class_subject->subject->type,
                    )
                );
            }
            ResponseService::successResponse("", $exam_data ?? []);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'parentApiController :- getExamDetails Method');
            ResponseService::errorResponse();
        }
    }

    public function getExamMarks(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            // Student Data
            $studentData = $this->student->findById($request->child_id, ['id', 'user_id', 'class_section_id'], ['class_section']);

            // Exam Result Data
            $examResultDB = $this->examResult->builder()->with([
                'user' => function ($q) {
                    $q->select('id','first_name','last_name')->with('student:id,user_id,roll_number');
                },
                'exam.timetable:id,exam_id,start_time,end_time',
                'session_year',
                'exam.marks' => function ($q) use ($studentData) {
                    $q->where('student_id', $studentData->user_id);
                }
            ])->where('student_id', $studentData->user_id)->get();


            // Check that Exam Result DB is not empty
            if (count($examResultDB)) {
                foreach ($examResultDB as $examResultData) {
                    $exam_result = array(
                        'result_id'         => $examResultData->id,
                        'exam_id'           => $examResultData->exam_id,
                        'exam_name'         => $examResultData->exam->name,
                        'class_name'        => $studentData->class_section->full_name,
                        'student_name'      => $examResultData->user->full_name,
                        'exam_date'         => $examResultData->exam->start_date,
                        'total_marks'       => $examResultData->total_marks,
                        'obtained_marks'    => $examResultData->obtained_marks,
                        'percentage'        => $examResultData->percentage,
                        'grade'             => $examResultData->grade,
                        'session_year'      => $examResultData->session_year->name,
                    );
                    $exam_marks = array();
                    foreach ($examResultData->exam->marks as $marks) {
                        $exam_marks[] = array(
                            'marks_id'          => $marks->id,
                            'subject_name'      => $marks->class_subject->subject->name,
                            'subject_type'      => $marks->class_subject->subject->type,
                            'total_marks'       => $marks->timetable->total_marks,
                            'obtained_marks'    => $marks->obtained_marks,
                            'teacher_review'    => $marks->teacher_review,
                            'grade'             => $marks->grade,
                        );
                    }
                    $data[] = array(
                        'result'        => $exam_result,
                        'exam_marks'    => $exam_marks,
                    );
                }
                ResponseService::successResponse("Exam Result Fetched Successfully", $data ?? null);
            } else {
                ResponseService::successResponse("Exam Result Fetched Successfully", []);
            }
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }


    /** ONLINE EXAM */

    public function getOnlineExamList(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required|numeric',
            'class_subject_id'  => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $this->student->findById($request->child_id, ['id', 'user_id', 'class_section_id','school_id'], ['class_section']);
            $classSectionId = $student->class_section->id;
            $sessionYear = $this->cache->getDefaultSessionYear($student->school_id);

            $onlineExamData = $this->onlineExam->builder()
                ->where(['class_section_id' => $classSectionId,'session_year_id' => $sessionYear->id])
                ->where('end_date', '>=', now())
                ->has('question_choice')
                ->with('class_subject','question_choice:id,online_exam_id,marks')
                ->whereDoesntHave('student_attempt', function ($q) use ($student) {
                    $q->where('student_id', $student->user_id);
                })
                ->when($request->class_subject_id, function ($query, $classSubjectId) {
                    return $query->where('class_subject_id', $classSubjectId);
                })
                ->orderby('start_date')
                ->paginate(15);

            ResponseService::successResponse('Data Fetched Successfully', $onlineExamData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Parent API ");
            ResponseService::errorResponse();
        }
    }

    public function getOnlineExamResultList(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required|numeric',
            'class_subject_id'  => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $this->student->findById($request->child_id, ['id', 'user_id', 'class_section_id', 'school_id'], ['class_section']);
            $classSectionId = $student->class_section_id;
            $sessionYear = $this->cache->getDefaultSessionYear($student->school_id);

            $studentSubjects = $student->selectedStudentSubjects()->pluck('class_subject_id');
           // Get Online Exam Data Where Logged in Student have attempted data and Relation Data with Question Choice , Student's answer with user submitted question with question and its option
            $onlineExamData = $this->onlineExam->builder()
                ->when($request->class_subject_id,function($query) use($request){
                    $query->where('class_subject_id',$request->class_subject_id);
                })
                ->where(['class_section_id' => $classSectionId,'session_year_id' => $sessionYear->id])
                ->whereHas('student_attempt', function ($q) use ($student) {
                    $q->where('student_id', $student->user_id);
                })
                ->whereIn('class_subject_id',$studentSubjects)
                ->with('question_choice:id,online_exam_id,marks','student_answers.user_submitted_questions.questions:id','student_answers.user_submitted_questions.questions.options:id,question_id,is_answer','class_subject.subject:id,name,type,code,bg_color,image')
            ->paginate(15)->toArray();

            $examListData = array(); // Initialized Empty examListData Array

            // Loop through Exam data
            foreach ($onlineExamData['data'] as $data) {

                // Get Total Marks of Particular Exam
                $totalMarks = collect($data['question_choice'])->sum('marks');

                // Initialized totalObtainedMarks with 0
                $totalObtainedMarks = 0;

                // Group Student's Answers by question_id
                $grouped_answers = [];
                foreach ($data['student_answers'] as $student_answer) {
                    $grouped_answers[$student_answer['question_id']][] = $student_answer;
                }

                // Loop through Student's Grouped answers
                foreach ($grouped_answers as $student_answers) {

                    // Filter the options whose is_answer values is 1
                    $correct_option_ids = array_filter($student_answers[0]['user_submitted_questions']['questions']['options'], function ($option) {
                        return $option['is_answer'] == 1;
                    });

                    // Get All Correct Options
                    $correct_option_ids = array_column($correct_option_ids, 'id');

                    // Get Student's Correct Options
                    $student_option_ids = array_column($student_answers, 'option_id');

                    // Check if the student's answers exactly match the correct answers then add marks with totalObtainedMarks
                    if (!array_diff($correct_option_ids, $student_option_ids) && !array_diff($student_option_ids, $correct_option_ids)) {
                        $totalObtainedMarks += $student_answers[0]['user_submitted_questions']['marks'];
                    }
                }

                // Make Exam List Data
                $examListData[] = array(
                    'online_exam_id'      => $data['id'],
                    'subject'             => array(
                        'id'   => $data['class_subject']['subject']['id'],
                        'name' => $data['class_subject']['subject']['name'] . ' - ' . $data['class_subject']['subject']['type'],
                    ),
                    'title'               => $data['title'],
                    'obtained_marks'      => $totalObtainedMarks ?? "0",
                    'total_marks'         => $totalMarks ?? "0",
                    'exam_submitted_date' => $exam_submitted_date ?? date('Y-m-d', strtotime($data['end_date']))
                );
            }

            $examList = array(
                'current_page' => $onlineExamData['current_page'],
                'data'         => $examListData,
                'from'         => $onlineExamData['from'],
                'last_page'    => $onlineExamData['last_page'],
                'per_page'     => $onlineExamData['per_page'],
                'to'           => $onlineExamData['to'],
                'total'        => $onlineExamData['total'],
            );

            ResponseService::successResponse("", $examList);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getOnlineExamResult(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required|numeric',
            'online_exam_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $this->student->findById($request->child_id, ['id', 'user_id', 'class_section_id'], ['class_section']);

             // Online Exam Data
             $onlineExam = $this->onlineExam->builder()
                ->where('id',$request->online_exam_id)
                ->whereHas('student_attempt', function ($q) use ($student) {
                    $q->where('student_id', $student->user_id);
                })
                ->with([
                        'question_choice:id,online_exam_id,marks',
                        'student_answers.user_submitted_questions.questions:id',
                        'student_answers.user_submitted_questions.questions.options:id,question_id,is_answer',
                    ])
            ->first();

            if(isset($onlineExam) && $onlineExam != null){

                //Get Total Question Count and Total Marks
                $totalQuestions = $onlineExam->question_choice->count();
                $totalMarks = $onlineExam->question_choice->sum('marks');

                // Group Student's Answers by question_id
                $grouped_answers = [];
                foreach ($onlineExam->student_answers as $student_answer) {
                    $grouped_answers[$student_answer['question_id']][] = $student_answer->toArray();
                }

                // Initialized the variables
                $correctQuestionData = array();
                $correctQuestions = 0;
                $totalObtainedMarks = "0";

                // Loop through Student's Grouped answers
                foreach ($grouped_answers as $student_answers) {

                    // Filter the options whose is_answer values is 1
                    $correct_option_ids = array_filter($student_answers[0]['user_submitted_questions']['questions']['options'], function ($option) {
                        return $option['is_answer'] == 1;
                    });

                    // Get All Correct Options
                    $correct_option_ids = array_column($correct_option_ids, 'id');

                    // Get Student's Correct Options
                    $student_option_ids = array_column($student_answers, 'option_id');

                    // Check if the student's answers exactly match the correct answers then add marks with totalObtainedMarks
                    if (!array_diff($correct_option_ids, $student_option_ids) && !array_diff($student_option_ids, $correct_option_ids)) {

                        // Sum Question marks with ObtainedMarks
                        $totalObtainedMarks += $student_answers[0]['user_submitted_questions']['marks'];

                        // Get Correct Questions Ids
                        $correctQuestionIds[] = $student_answers[0]['user_submitted_questions']['id'];

                        // Increment Correct Question by 1
                        ++$correctQuestions;

                        // Correct Question Data
                        $correctQuestionData[] = array(
                            'question_id'   => $student_answers[0]['user_submitted_questions']['id'],
                            'marks'         => $student_answers[0]['user_submitted_questions']['marks']
                        );
                    }
                }


                // Check correctQuestionIds exists and not empty
                if (!empty($correctQuestionIds)) {
                    // Get Incorrect Questions Excluding Correct answer using correctQuestionIds
                    $incorrectQuestionsData = $onlineExam->question_choice->whereNotIn('id',$correctQuestionIds);
                }else{
                    // Get All Question Choice as incorrectQuestionsData
                    $incorrectQuestionsData = $onlineExam->question_choice;
                }

                // Total Incorrect Questions
                $incorrectQuestions = $incorrectQuestionsData->count();

                // Incorrect Question Data
                $inCorrectQuestionData = array();
                foreach ($incorrectQuestionsData as $incorrectData) {
                    $inCorrectQuestionData[] = array(
                        'question_id'   => $incorrectData->id,
                        'marks'         => $incorrectData->marks
                    );
                }

                // Final Array Data
                $onlineExamResult = array(
                    'total_questions'       => $totalQuestions ?? '0',
                    'correct_answers'       => array(
                        'total_questions' => $correctQuestions ?? '',
                        'question_data' => $correctQuestionData
                    ),
                    'in_correct_answers'    => array(
                        'total_questions' => $incorrectQuestions ?? '',
                        'question_data'   => $inCorrectQuestionData ?? ''
                    ),
                    'total_obtained_marks'  => $totalObtainedMarks,
                    'total_marks'           => $totalMarks ?? '0'
                );
                ResponseService::successResponse("", $onlineExamResult);
            }else{
                ResponseService::successResponse("", []);
            }
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getOnlineExamReport(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required|numeric',
            'class_subject_id'  => 'required|numeric'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $this->student->findById($request->child_id, ['id', 'user_id', 'class_section_id','school_id'], ['class_section']);
            $sessionYear = $this->cache->getDefaultSessionYear($student->school_id);

            $onlineExams = $this->onlineExam->builder()
            ->has('question_choice')
            ->where(['class_section_id' => $student->class_section_id, 'class_subject_id' => $request->class_subject_id, 'session_year_id' => $sessionYear->id])
                ->whereHas('student_attempt', function ($q) use ($student) {
                    $q->where('student_id', $student->user_id);
                })
                ->with([
                    'question_choice:id,online_exam_id,marks',
                    'student_answers.user_submitted_questions.questions:id',
                    'student_answers.user_submitted_questions.questions.options:id,question_id,is_answer',
                    'class_subject.subject:id,name,type,code,bg_color,image'
                ])
            ->paginate(10);

        if ($onlineExams->count() > 0) {
            $totalExamIds = $onlineExams->pluck('id')->toArray();
            $totalExamsAttempted = $this->user->builder()->role('Student')->where('id', $student->user_id)->has('online_exam_attempts')->count();

            $examList = array();
            foreach ($onlineExams->toArray()['data'] as $onlineExam) {
                $totalMarks = collect($onlineExam['question_choice'])->sum('marks');

                // Initialized totalObtainedMarks with 0
                $totalObtainedMarks = "0";

                // Group Student's Answers by question_id
                $grouped_answers = [];
                foreach ($onlineExam['student_answers'] as $student_answer) {
                    $grouped_answers[$student_answer['question_id']][] = $student_answer;
                }

                // Loop through Student's Grouped answers
                foreach ($grouped_answers as $student_answers) {

                    // Filter the options whose is_answer values is 1
                    $correct_option_ids = array_filter($student_answers[0]['user_submitted_questions']['questions']['options'], static function ($option) {
                        return $option['is_answer'] == 1;
                    });

                    // Get All Correct Options
                    $correct_option_ids = array_column($correct_option_ids, 'id');

                    // Get Student's Correct Options
                    $student_option_ids = array_column($student_answers, 'option_id');

                    // Check if the student's answers exactly match the correct answers then add marks with totalObtainedMarks
                    if (!array_diff($correct_option_ids, $student_option_ids) && !array_diff($student_option_ids, $correct_option_ids)) {
                        $totalObtainedMarks += $student_answers[0]['user_submitted_questions']['marks'];
                    }
                }

                // Add exam to the list
                $examList[] =  [
                    'online_exam_id'    => $onlineExam['id'],
                    'title'             => $onlineExam['title'],
                    'obtained_marks'    => (string)$totalObtainedMarks,
                    'total_marks'       => (string)$totalMarks,
                ];
            }


            // Calculate Percentage
            if ($totalMarks > 0) {
                // Avoid division by zero error
                $percentage = number_format(($totalObtainedMarks * 100) / max($totalMarks, 1), 2);
            } else {
                // If total marks is zero, then percentage is also zero
                $percentage = 0;
            }

            // Build the final data array
            $onlineExamReportData = array(
                'total_exams' => count($totalExamIds),
                'attempted'    => $totalExamsAttempted,
                'missed_exams' => count($totalExamIds) - $totalExamsAttempted,
                'total_marks' => (string)$totalMarks,
                'total_obtained_marks' => (string)$totalObtainedMarks,
                'percentage' => (string)$percentage,
                'exam_list' => [
                    'current_page' => (string)$onlineExams->currentPage(),
                    'data' => array_values($examList),
                    'from' => (string)$onlineExams->firstItem(),
                    'last_page' => (string)$onlineExams->lastPage(),
                    'per_page' => (string)$onlineExams->perPage(),
                    'to' => (string)$onlineExams->lastItem(),
                    'total' => (string)$onlineExams->total(),
                ],
            );
        } else {
            $onlineExamReportData = [];
        }


        // Return the response
        ResponseService::successResponse("", $onlineExamReportData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getAssignmentReport(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required|numeric',
            'class_subject_id'  => 'required|numeric'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $this->student->findById($request->child_id, ['id', 'user_id', 'class_section_id','school_id'], ['class_section']);
            $sessionYear = $this->cache->getDefaultSessionYear($student->school_id);

            // Assignment Data
            $assignments = $this->assignment->builder()
                ->where(['class_section_id' => $student->class_section_id, 'session_year_id' => $sessionYear->id, 'class_subject_id' => $request->class_subject_id])->whereNotNull('points')
            ->get();

            // Get the assignment submissions
            $submitted_assignment_ids = $this->assignmentSubmission->builder()->where('student_id', $student->user_id)->whereIn('assignment_id', $assignments->pluck('id'))->pluck('assignment_id');

            // Calculate various statistics
            $total_assignments = $assignments->count();
            $total_submitted_assignments = $submitted_assignment_ids->count();
            $total_assignment_submitted_points = $assignments->sum('points');
            $total_points_obtained = $this->assignmentSubmission->builder()->whereIn('assignment_id', $submitted_assignment_ids)->sum('points');

            // Calculate the percentage
            $percentage = $total_assignment_submitted_points ? number_format(($total_points_obtained * 100) / $total_assignment_submitted_points, 2) : 0;

            // Get the submitted assignment data with points (using pagination manually)
            $perPage = 15;
            $currentPage = $request->input('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            $submitted_assignment_data_with_points = $assignments->filter(function ($assignment) use ($submitted_assignment_ids) {
                return $assignment->points !== null && $submitted_assignment_ids->contains($assignment->id);
            })->slice($offset, $perPage)->map(function ($assignment) {
                return [
                    'assignment_id'     => $assignment->id,
                    'assignment_name'   => $assignment->name,
                    'obtained_points'   => optional($assignment->submission)->points ?? 0,
                    'total_points'      => $assignment->points
                ];
            });

            $assignment_report = [
                'assignments'                           => $total_assignments,
                'submitted_assignments'                 => $total_submitted_assignments,
                'unsubmitted_assignments'               => $total_assignments - $total_submitted_assignments,
                'total_points'                          => $total_assignment_submitted_points,
                'total_obtained_points'                 => $total_points_obtained,
                'percentage'                            => $percentage,
                'submitted_assignment_with_points_data' => [
                    'current_page'  => $currentPage,
                    'data'          => array_values($submitted_assignment_data_with_points->toArray()),
                    'from'          => $offset + 1,
                    'to'            => $offset + $submitted_assignment_data_with_points->count(),
                    'per_page'      => $perPage,
                    'total'         => $total_submitted_assignments,
                    'last_page'     => ceil($total_submitted_assignments / $perPage),
                ],
            ];


            ResponseService::successResponse("Data Fetched Successfully", $assignment_report);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /** FEES */

    //Get Fees Details
    public function getFeesDetails(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required',
            'session_year_id'   => 'nullable'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $studentData = $this->student->findById($request->child_id, ['id', 'user_id', 'class_section_id'], ['class_section']);
            $classId = $studentData->class_section->class_id;
            $schoolId = $studentData->user->school_id;

            $currentSessionYear = $this->cache->getDefaultSessionYear($schoolId);
            $sessionYearId = $request->session_year_id ?? $currentSessionYear->id;

            $feesQueryData = $this->fees->builder()
                ->with([
                    'fees_class' => function ($query) use ($studentData, $classId) {
                        $query->where('class_id', $classId)->with(['fees_type', 'optional_fees' => function ($query) use ($studentData) {
                            $query->where('student_id', $studentData->user_id);
                        }]);
                    },
                    'installments.compulsory_fees' => function ($query) use ($studentData, $classId) {
                        $query->where(['class_id' => $classId, 'student_id' => $studentData->user_id]);
                    },
                    'fees_paid' => function ($query) use ($studentData, $classId, $sessionYearId) {
                        $query->where(['student_id' => $studentData->user_id, 'class_id' => $classId, 'session_year_id' => $sessionYearId])->with('compulsory_fee', 'optional_fee');
                    }
                ])
                ->get();

            $feesData = array();

            foreach ($feesQueryData as $feeData) {
                $totalCompulsoryFeesAmount = $feeData->compulsory_fees->sum('amount');
                $totalOptionalFeesAmount = $feeData->optional_fees->sum('amount');

                $optionalFeesData = array();
                $compulsoryFeeData = array();
                $installmentFeesData = array();

                foreach ($feeData->optional_fees as $optionalFee) {
                    $optionalFeesData[] = array(
                        'id'           => $optionalFee->id,
                        'fees_type_id' => $optionalFee->fees_type_id,
                        'name'         => $optionalFee->fees_type->name,
                        'amount'       => $optionalFee->amount,
                        'is_paid'      => $optionalFee->optional_fees->count() ? 1 : 0,
                        'paid_date'    => $optionalFee->optional_fees->count() ? $optionalFee->optional_fees->first()->date : "",
                    );
                }
                foreach ($feeData->compulsory_fees as $compulsoryFee) {
                    $compulsoryFeeData[] = array(
                        'id'           => $compulsoryFee->id,
                        'fees_type_id' => $compulsoryFee->fees_type_id,
                        'name'         => $compulsoryFee->fees_type->name,
                        'amount'       => $compulsoryFee->amount,
                        'is_paid'      => $feeData->fees_paid->count() && $feeData->fees_paid->first()->is_fully_paid == 1 ? 1 : 0,
                        'paid_on'      => $feeData->fees_paid->count() && $feeData->fees_paid->first()->is_fully_paid == 1 ? $feeData->fees_paid->first()->date : ""
                    );
                }

                if ($feeData->include_fee_installments == 1) {
                    foreach ($feeData->installments as $installment) {
                        $installmentFeesData[] = array(
                            'id'          => $installment->id,
                            'name'        => $installment->name,
                            'due_date'    => $installment->due_date,
                            'due_charges' => $installment->due_charges,
                            'is_paid'     => $installment->compulsory_fees->count() ? 1 : 0,
                            'paid_date'   => $installment->compulsory_fees->count() ? $installment->compulsory_fees->first()->date : "",
                        );
                    }
                }

                $feesData[] = array(
                    'fee_id'                 => $feeData->id,
                    'fee_name'               => $feeData->name,
                    'compulsory_fees_data'   => $compulsoryFeeData ?? [],
                    'optional_fees_data'     => $optionalFeesData ?? [],
                    'installment_data'       => $installmentFeesData,
                    'compulsory_fees_total'  => $totalCompulsoryFeesAmount ?? 0,
                    'optional_fees_total'    => $totalOptionalFeesAmount ?? 0,
                    'compulsory_due_date'    => date('d-m-Y', strtotime($feeData->due_date)),
                    'compulsory_due_charges' => $feeData->due_charges,
                    // 'is_fee_pending'            => $isFeePending,
                );
            }
            ResponseService::successResponse("", $feesData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    /**
     * TODO
     * Due Charges are left to be Calculate
     */
    public function makeFeesTransaction(Request $request){
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required',
            'type_of_fee'       => 'required|in:1,2,3',
            'fees_id'           => 'required',
            'installment_id'    => 'array|required_if:type_of_fee,2|nullable',
            'optional_id'       => 'array|required_if:type_of_fee,3|nullable',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $parentId = Auth::user()->id;
            $studentData = $this->student->findById($request->child_id,['id','user_id','class_section_id','school_id'],['class_section']);
            $classId = $studentData->class_section->class_id;
            $schoolId = $studentData->user->school_id;
            $sessionYear = $this->cache->getDefaultSessionYear($schoolId);

            // Initialize the Empty Variables
            $paymentGatewayDetails = array();

            // Get Settings data form the cache
            $paymentConfigrations = $this->paymentConfigrations->all();
            $stripeData = $paymentConfigrations->where('payment_method','stripe')->first();
            $stripeStatus = $stripeData->status;
            //validating the enable of gateways ..
            if ($stripeStatus == 0) {
                ResponseService::errorResponse("Enable Stripe Payment Gateway",[],config('constants.RESPONSE_CODE.ENABLE_PAYMENT_GATEWAY'));
            }else{
                // Add Payment Data to Payment Transactions Table
                $paymentTransactionData = $this->paymentTransaction->create([
                    'user_id'           => $parentId,
                    'amount'            => 0,
                    'payment_gateway'   => '2',
                    'payment_status'    => '2',
                    'school_id'         => $schoolId
                ]);

                // Fees Data
                $feesData = $this->fees->builder()
                    ->where('id', $request->fees_id)
                    ->with([
                        'fees_class'                   => function ($query) use ($studentData, $classId) {
                            $query->where('class_id', $classId)->with(['fees_type', 'optional_fees' => function ($query) use ($studentData) {
                                $query->where('student_id', $studentData->user_id);
                            }]);
                        },
                        'installments.compulsory_fees' => function ($query) use ($studentData, $classId) {
                            $query->where(['class_id' => $classId, 'student_id' => $studentData->user_id]);
                        },
                        'fees_paid'                    => function ($query) use ($studentData, $classId) {
                            $query->where(['student_id' => $studentData->user_id, 'class_id' => $classId])->with('compulsory_fee', 'optional_fee');
                        }
                    ])
                    ->first();

                // Compulsory Full Amount
                $compulsoryFullAmount = $feesData->compulsory_fees->sum('amount');

                $currentDate = date('Y-m-d');

                $amount = 0;
                $isFullyPaid = "0";

                if ($request->type_of_fee == 1) {
                    // If Fee type is Compulsory

                    // Check that fee is paid or not
                    if ($feesData->fees_paid->count()) {
                        ResponseService::errorResponse("Fees Already Paid", "", config('constants.RESPONSE_CODE.FEE_ALREADY_PAID'));
                    } else if (strtotime($feesData->due_date) < strtotime($currentDate)) {
                        // Calculate Due Charges on compulsory Amount based on Due Charge's Percentage
                        $dueChargesAmount = round(($compulsoryFullAmount * $feesData->due_charges) / 100, 2);
                        $compulsoryFullAmountWithDueCharges = round(($compulsoryFullAmount + $dueChargesAmount), 2);
                        $amount = $compulsoryFullAmountWithDueCharges;
                    } else {
                        $amount = $compulsoryFullAmount;
                    }


                    $this->compulsoryFee->updateOrCreate(
                        [
                            'student_id'    => $studentData->user_id,
                            'class_id'      => $classId,
                            'fees_paid_id'  => null,
                            'status'        => "2",
                            'type'          => "1",
                            'school_id'     => $schoolId
                        ],
                        [
                            'payment_transaction_id' => $paymentTransactionData->id,
                            'amount'                 => $amount,
                            'due_charges'            => $due_charges_amount ?? null,
                            'date'                   => date('Y-m-d', strtotime($currentDate)),
                        ]
                    );

                    $isFullyPaid = "1";
                } else if ($request->type_of_fee == 2) {
                    // If Fee type is Installment

                    $installmentsWithCompulsoryFees = array_filter($feesData->installments->toArray(), static function ($installment) {
                        return !empty($installment['compulsory_fees']);
                    });
                    $installmentPaidCounter = count($installmentsWithCompulsoryFees);

                    // Get installment Data of installment ids passed in request
                    $installments = $feesData->installments->whereIn('id', $request->installment_id);
                    foreach ($installments as $installment) {

                        //Check the installment is paid or not
                        if ($installment->compulsory_fees->count()) {
                            ResponseService::errorResponse("Fees Already Paid please select only unpaid fees", "", config('constants.RESPONSE_CODE.FEE_ALREADY_PAID'));
                        }

                        // Make Compulsory Amount divided equally to the number of installments
                        $installmentAmount = round($compulsoryFullAmount / count($feesData->installments), 2);
                        if (strtotime($installment->due_date) < strtotime($currentDate)) {
                            // Calculate Due Charges on compulsory Amount based on Due Charge's Percentage
                            $dueChargesAmount = round(($installmentAmount * $installment->due_charges) / 100, 2);
                            $installmentAmountWithDueCharges = round(($installmentAmount + $dueChargesAmount), 2);
                            $amount += $installmentAmountWithDueCharges;
                        } else {
                            $amount += $installmentAmount;
                        }

                        // Make Increment With Installment Compulsory Pay
                        $installmentPaidCounter += $installmentPaidCounter;

                        $this->compulsoryFee->updateOrCreate(
                            [
                                'student_id'    => $studentData->user_id,
                                'class_id'      => $classId,
                                'fees_paid_id'  => null,
                                'status'        => "2",
                                'type'          => "2",
                                'school_id'     => $schoolId
                            ],
                            [
                                'payment_transaction_id' => $paymentTransactionData->id,
                                'installment_id'         => $installment['id'],
                                'amount'                 => $installmentAmountWithDueChagrges ?? $installmentAmount,
                                'due_charges'            => $dueChargesAmount ?? null,
                                'date'                   => date('Y-m-d', strtotime($currentDate)),
                            ]
                        );
                    }


                    if ($feesData->installments->count() == $installmentPaidCounter) {
                        $isFullyPaid = "1";
                    }
                }else {
                    // Get Optional Data from the ids form request
                    $optionalFees = $feesData->optional_fees->whereIn('id', $request->optional_id);
                    $paidOptionalIds = array();
                    foreach ($optionalFees as $optionalFee) {

                        // Check that if optional fees are paid or not
                        if ($optionalFee->optional_fees->count()) {
                            $paidOptionalIds[] = $optionalFee->optional_fees->first()->fees_class_id; // Make an array of paid optional ids
                            ResponseService::errorResponse("Fees Already Paid please select only unpaid fees","",config('constants.RESPONSE_CODE.FEE_ALREADY_PAID'));
                            break;
                        }

                        $this->optionalFees->updateOrCreate(
                            [
                                'student_id'    => $studentData->user_id,
                                'class_id'      => $classId,
                                'fees_paid_id'  => null,
                                'status'        => "2",
                                'fees_class_id' => $optionalFee->id,
                                'school_id'     => $schoolId
                            ],
                            [
                                'payment_transaction_id'    => $paymentTransactionData->id,
                                'amount'                    => $optionalFee->amount,
                                'due_charges'               => $due_charges_amount ?? null,
                                'date'                      => date('Y-m-d',strtotime($currentDate)),
                            ]
                        );
                    }


                    // Get Amount Of Optional Fees who is not paid
                    $amount = $feesData->optional_fees->whereIn('id', $request->optional_id)->whereNotIn('id', $paidOptionalIds)->sum('amount');
                }

                $this->paymentTransaction->update($paymentTransactionData->id,['amount' => $amount, 'school_id' => $schoolId]);


                $currency_code = strtolower($this->cache->getSchoolSettings('currency_code',$schoolId));
                $stripeSecretKey = $stripeData->secret_key;

                // Call Stripe Class and Create Payment Intent
                $stripe = new StripeClient($stripeSecretKey);
                $stripeData = $stripe->paymentIntents->create(
                    [
                        'amount'   => $amount * 100,
                        'currency' => $currency_code,
                        'metadata' => [
                            'fees_id'                => $request->fees_id,
                            'student_id'             => $studentData->user_id,
                            'class_id'               => $classId,
                            'parent_id'              => $parentId,
                            'session_year_id'        => $sessionYear->id,
                            'payment_transaction_id' => $paymentTransactionData->id,
                            'type_of_fee'            => $request->type_of_fee,
                            'is_full_paid'           => $isFullyPaid,
                        ],
                    ]
                );
                // Update Payment Transaction data with the payment intent id generated by stripe
                $this->paymentTransaction->update($paymentTransactionData->id,['order_id' => $stripeData->id, 'school_id' => $schoolId]);

                // Custom Array to Show as response
                $paymentGatewayDetails = array(
                    'payment_intent_id'      => $stripeData->id,
                    'amount'                 => $stripeData->amount,
                    'client_secret'          => $stripeData->client_secret,
                    'payment_transaction_id' => $paymentTransactionData->id,
                );
            }

            DB::commit();
            ResponseService::successResponse("", $paymentGatewayDetails);
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    // add the transaction data in transaction table
    public function completeFeeTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id'                  => 'required',
            'payment_transaction_id'    => 'required',
            'payment_id'                => 'required',
            'payment_signature'         => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $child = $this->student->findById($request->child_id);
            $this->paymentTransaction->update($request->payment_transaction_id,['payment_id' => $request->payment_id, 'payment_signature' => $request->payment_signature,'school_id' => $child->school_id]);
            DB::commit();
            ResponseService::successResponse("Data Updated Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    //get the fees paid list
    public function feesPaidList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required',
            'session_year_id'   => 'nullable'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $child = $this->student->findById($request->child_id);
            $currentSessionYear = $this->cache->getDefaultSessionYear($child->user->school_id);
            $sessionYearId = $request->session_year_id ?? $currentSessionYear->id;
            $fees_paid = $this->feesPaid->builder()->where(['student_id' => $child->user_id,'session_year_id' => $sessionYearId])->with('session_year:id,name', 'class.medium')->get();

            ResponseService::successResponse("",$fees_paid);
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
        return response()->json($response);
    }

    //Generate The Receipt
    public function feesPaidReceiptPDF(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required',
            'session_year_id'   => 'nullable'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $child = $this->student->findById($request->child_id);
            $schoolId = $child->user->school_id;
            $currentSessionYear = $this->cache->getDefaultSessionYear($schoolId);
            $sessionYearId = $request->session_year_id ?? $currentSessionYear->id;

            $parent_name = Auth::user()->full_name;
            $logo = $this->cache->getSystemSettings('logo');
            $school_name = $this->cache->getSchoolSettings('school_name',$schoolId);
            $school_address = $this->cache->getSchoolSettings('school_address',$schoolId);

            $currency_symbol = $this->cache->getSchoolSettings('currency_symbol',$schoolId) ?? NULL;

            //Getting the Fees Paid Data
            $feesPaid = $this->feesPaid->builder()->where('id',$request->fees_paid_id)
            ->with([
                'student:id,first_name,last_name',
                'class',
                'session_year',
                'fees.fees_class',
                'compulsory_fee.installment_fee:id,name',
                'optional_fee' => function($q){
                    $q->with(['fees_class' => function($q){
                        $q->select('id','fees_type_id')->with('fees_type:id,name');
                    }]);
                },
            ])->first();

            $verticalLogo = $this->cache->getSystemSettings()['vertical_logo'] ?? NULL; // Get Vertical Logo
            $logo = $verticalLogo ?? url('assets/vertical-logo2.svg');
            $schoolName = $this->cache->getSchoolSettings('school_name');
            $schoolAddress = $this->cache->getSchoolSettings('school_address');
            $currencySymbol = $this->cache->getSchoolSettings('currency_symbol') ?? NULL;

            //Load the HTML
            $pdf = Pdf::loadView('fees.fees_receipt', compact('logo', 'schoolName', 'schoolAddress', 'currencySymbol', 'feesPaid'));

            //Get The Output Of PDF
            $output = $pdf->output();

            $response = array(
                'error' => false,
                'pdf' => base64_encode($output),
            );
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
        return response()->json($response);
    }

    // // Make Transaction Fail API
    // public function failPaymentTransactionStatus(Request $request){
    //     try{
    //         $update_status = PaymentTransaction::findOrFail($request->payment_transaction_id);
    //         $update_status->payment_status = 0;
    //         $update_status->save();
    //         $response = array(
    //             'error' => false,
    //             'message' => 'Data Updated Successfully',
    //             'code' => 200,
    //         );
    //     } catch (\Exception $e) {
    //         $response = array(
    //             'error' => true,
    //             'message' => trans('error_occurred'),
    //             'code' => 103,
    //         );
    //     }
    // }

    public function getSchoolSettings(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required|numeric',
            'session_year_id'   => 'nullable'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $child = $this->student->findById($request->child_id);
            $settings = $this->cache->getSchoolSettings(['*'], $child->user->school_id);
            $sessionYear = $this->cache->getDefaultSessionYear($child->user->school_id);
            $semester = $this->cache->getDefaultSemesterData($child->user->school_id);
            $features = $this->featureService->getFeatures($child->user->school_id);
            $data = [
                'school_id'    => $child->user->school_id,
                'settings' => $settings,
                'session_year' => $sessionYear,
                'semester'     => $semester,
                'features' => $features
            ];
            ResponseService::successResponse('Settings Fetched Successfully.', $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getSliders(Request $request) {
        $validator = Validator::make($request->all(), [
            'child_id'          => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $child = $this->student->findById($request->child_id);
            $data = $this->sliders->builder()->where('school_id',$child->user->school_id)->get();
            ResponseService::successResponse("Sliders Fetched Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }
}
