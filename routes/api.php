<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\ParentApiController;
use App\Http\Controllers\Api\StaffApiController;
use App\Http\Controllers\Api\StaffManageApiController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\TeacherApiController;
use App\Http\Controllers\Api\StaffSliderApiController;
use App\Http\Controllers\Api\StaffTeacherApiController;
use App\Http\Controllers\Api\StudentPickupController;
use App\Http\Controllers\SubscriptionWebhookController;
use App\Http\Controllers\Api\SessionYearApiController;
use App\Http\Controllers\Api\StaffExamApiController;
use App\Http\Controllers\Api\StaffGradeApiController;
use App\Http\Controllers\Api\StaffExamResultApiController;
use App\Http\Controllers\Api\StaffOnlineExamApiController;
use App\Http\Controllers\Api\StaffOnlineExamQuestionApiController;
use App\Http\Controllers\Api\StaffOnlineExamResultApiController;
use App\Http\Controllers\Api\StaffLessonApiController;
use App\Http\Controllers\Api\StaffLessonTopicApiController;
use App\Http\Controllers\Api\StaffAttendanceApiController;
use App\Http\Controllers\Api\StaffLeaveApiController;
use App\Http\Controllers\Api\StaffFeesApiController;
use App\Http\Controllers\Api\StaffAssignmentApiController;
use App\Http\Controllers\Api\StaffAnnouncementApiController;
use App\Http\Controllers\Api\StaffGalleryApiController;
use App\Http\Controllers\Api\HolidayApiController;
use App\Http\Controllers\Api\TimetableApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 * Webhook Routes
 **/
Route::post('subscription/webhook/stripe', [SubscriptionWebhookController::class, 'stripe']);
Route::post('subscription/webhook/razorpay', [SubscriptionWebhookController::class, 'razorpay']);

// Route::group(['middleware' => 'auth:sanctum'], static function () {
//     Route::post('logout', [ApiController::class, 'logout']);
// });

Route::get('schools', [ApiController::class, 'getAllSchools']);


Route::group(['middleware' => 'APISwitchDatabase'], static function () {
    Route::post('logout', [ApiController::class, 'logout']);
    Route::post('staff-attendance/scan', [\App\Http\Controllers\StaffAttendanceController::class, 'scan']);
});
Route::get('fees-due-notification',[ApiController::class, 'sendFeeNotification']);
/**
 * STUDENT APIs
 **/
Route::group(['prefix' => 'student'], static function () {

    //Non Authenticated APIs
    Route::post('login', [StudentApiController::class, 'login']);
    Route::post('forgot-password', [StudentApiController::class, 'forgotPassword']);

    //Authenticated APIs
    Route::group(['middleware' => ['APISwitchDatabase', 'checkSchoolStatus']], static function () {
        Route::get('class-subjects', [StudentApiController::class, 'classSubjects']);
        Route::get('subjects', [StudentApiController::class, 'subjects']);
        Route::post('select-subjects', [StudentApiController::class, 'selectSubjects']);
        Route::get('guradian-details', [StudentApiController::class, 'getGuardianDetails']);
        Route::get('timetable', [StudentApiController::class, 'getTimetable']);
        Route::get('lessons', [StudentApiController::class, 'getLessons']);
        Route::get('lesson-topics', [StudentApiController::class, 'getLessonTopics']);
        Route::get('assignments', [StudentApiController::class, 'getAssignments']);
        Route::post('submit-assignment', [StudentApiController::class, 'submitAssignment']);
        Route::post('delete-assignment-submission', [StudentApiController::class, 'deleteAssignmentSubmission']);
        Route::get('attendance', [StudentApiController::class, 'getAttendance']);
        Route::get('announcements', [StudentApiController::class, 'getAnnouncements']);
        Route::get('get-exam-list', [StudentApiController::class, 'getExamList']); // Exam list Route
        Route::get('get-exam-details', [StudentApiController::class, 'getExamDetails']); // Exam Details Route
        Route::get('exam-marks', [StudentApiController::class, 'getExamMarks']); // Exam Details Route
        Route::get('sliders', [StudentApiController::class, 'getSliders']); // Sliders

        // online exam routes
        Route::get('get-online-exam-list', [StudentApiController::class, 'getOnlineExamList']); // Get Online Exam List Route
        Route::get('get-online-exam-questions', [StudentApiController::class, 'getOnlineExamQuestions']); // Get Online Exam Questions Route
        Route::post('submit-online-exam-answers', [StudentApiController::class, 'submitOnlineExamAnswers']); // Submit Online Exam Answers Details Route
        Route::get('get-online-exam-result-list', [StudentApiController::class, 'getOnlineExamResultList']); // Online exam result list Route
        Route::get('get-online-exam-result', [StudentApiController::class, 'getOnlineExamResult']); // Online exam result  Route

        //reports
        Route::get('get-online-exam-report', [StudentApiController::class, 'getOnlineExamReport']); // Online Exam Report Route
        Route::get('get-assignments-report', [StudentApiController::class, 'getAssignmentReport']); // Assignment Report Route

        // profile data
        Route::get('get-profile-data', [StudentApiController::class, 'getProfileDetails']); // Get Profile Data

        // Session Year
        Route::get('current-session-year', [StudentApiController::class, 'getSessionYear']);

        Route::get('school-settings', [StudentApiController::class, 'getSchoolSettings']);


        // student diaries
        Route::get('/diaries', [StudentApiController::class, 'getStudentDiaries']);
        Route::get('/diary-details', [StudentApiController::class, 'showStudentDiaryDetail']);
        Route::get('dashboard', [StudentApiController::class, 'dashboard']);
    });
});

/**
 * PARENT APIs
 **/
Route::group(['prefix' => 'parent'], static function () {
    //Non Authenticated APIs
    // Route::group(['middleware' => ['APISwitchDatabase']], static function () {
        Route::post('login', [ParentApiController::class, 'login']);
        //Authenticated APIs
        // Route::group(['middleware' => ['']], static function () {
        //     Route::get('test', [ParentApiController::class, 'test']);
        // });
        Route::post('forgot-password', [ParentApiController::class, 'forgotPassword']);
        Route::post('reset-password', [ParentApiController::class, 'resetPassword']);

        Route::group(['middleware' => ['APISwitchDatabase']], static function () {
            Route::get('test', [ParentApiController::class, 'test']);

            Route::group(['middleware' => ['checkChild','APISwitchDatabase']], static function () {
                Route::get('subjects', [ParentApiController::class, 'subjects']);
                Route::get('class-subjects', [ParentApiController::class, 'classSubjects']);
                Route::post('consent-form', [ParentApiController::class, 'updateConsentFormDate']);
                Route::get('timetable', [ParentApiController::class, 'getTimetable']);
                Route::get('lessons', [ParentApiController::class, 'getLessons']);
                Route::get('lesson-topics', [ParentApiController::class, 'getLessonTopics']);
                Route::get('assignments', [ParentApiController::class, 'getAssignments']);
                Route::get('attendance', [ParentApiController::class, 'getAttendance']);
                Route::get('teachers', [ParentApiController::class, 'getTeachers']);
                Route::get('sliders', [ParentApiController::class, 'getSliders']); // Sliders

                // Offline Exams
                Route::get('get-exam-list', [ParentApiController::class, 'getExamList']); // Exam list Route
                Route::get('get-exam-details', [ParentApiController::class, 'getExamDetails']); // Exam Details Route
                Route::get('exam-marks', [ParentApiController::class, 'getExamMarks']); //Exam Marks

                // Fees

                Route::group(['prefix' => 'fees'], static function () {
                    Route::get('/', [ParentApiController::class, 'getFees']);
                    Route::post('/compulsory/pay', [ParentApiController::class, 'payCompulsoryFees']);
                    Route::post('/optional/pay', [ParentApiController::class, 'payOptionalFees']);
                    Route::get('/receipt', [ParentApiController::class, 'feesPaidReceiptPDF']); //Fees Receipt
                    Route::get('/fees-paid-list', [ParentApiController::class, 'feesPaidList']);
                    Route::get('/fees-failed-list', [ParentApiController::class, 'feesFailedList']);
                });


                // Online Exam
                Route::get('get-online-exam-list', [ParentApiController::class, 'getOnlineExamList']); // Get Online Exam List Route
                Route::get('get-online-exam-result-list', [ParentApiController::class, 'getOnlineExamResultList']); // Online exam result list Route
                Route::get('get-online-exam-result', [ParentApiController::class, 'getOnlineExamResult']); // Online exam result  Route

                // Reports
                Route::get('get-online-exam-report', [ParentApiController::class, 'getOnlineExamReport']); // Online Exam Report Route
                Route::get('get-assignments-report', [ParentApiController::class, 'getAssignmentReport']); // Assignment Report Route

                // Session Year
                Route::get('current-session-year', [ParentApiController::class, 'getSessionYear']);
                Route::get('school-settings', [ParentApiController::class, 'getSchoolSettings']);
                Route::get('notifications', [ParentApiController::class, 'getNotifications']);

                // profile data
                Route::get('get-child-profile-data', [ParentApiController::class, 'getChildProfileDetails']); // Get Profile Data

                // Announcements
                Route::get('announcements', [ParentApiController::class, 'getAnnouncements']);


                // student diaries
                Route::get('/diaries', [ParentApiController::class, 'getStudentDiaries']);
                Route::get('/diary-details', [ParentApiController::class, 'showStudentDiaryDetail']);

                // Student Pickup
                Route::get('student-pickup-request', [StudentPickupController::class, 'getStudentPickupRequests']);
                Route::post('student-pickup-request', [StudentPickupController::class, 'createPickupRequest']);
            });
        });
    // });
});

/**
 * TEACHER APIs
 **/
Route::group(['prefix' => 'teacher'], static function () {
    //Non Authenticated APIs
    Route::post('login', [TeacherApiController::class, 'login']);
    //Authenticated APIs
    Route::group(['middleware' => ['APISwitchDatabase', 'checkSchoolStatus', 'teacherOnboarding']], static function () {

        Route::get('subjects', [TeacherApiController::class, 'subjects']);

        //Assignment
        Route::get('get-assignment', [TeacherApiController::class, 'getAssignment']);
        Route::post('create-assignment', [TeacherApiController::class, 'createAssignment']);
        Route::post('update-assignment', [TeacherApiController::class, 'updateAssignment']);
        Route::post('delete-assignment', [TeacherApiController::class, 'deleteAssignment']);

        //Assignment Submission
        Route::get('get-assignment-submission', [TeacherApiController::class, 'getAssignmentSubmission']);
        Route::post('update-assignment-submission', [TeacherApiController::class, 'updateAssignmentSubmission']);

        //File
        Route::post('delete-file', [TeacherApiController::class, 'deleteFile']);
        Route::post('update-file', [TeacherApiController::class, 'updateFile']);

        //Lesson
        Route::get('get-lesson', [TeacherApiController::class, 'getLesson']);
        Route::post('create-lesson', [TeacherApiController::class, 'createLesson']);
        Route::post('update-lesson', [TeacherApiController::class, 'updateLesson']);
        Route::post('delete-lesson', [TeacherApiController::class, 'deleteLesson']);

        //Topic
        Route::get('get-topic', [TeacherApiController::class, 'getTopic']);
        Route::post('create-topic', [TeacherApiController::class, 'createTopic']);
        Route::post('update-topic', [TeacherApiController::class, 'updateTopic']);
        Route::post('delete-topic', [TeacherApiController::class, 'deleteTopic']);

        //Announcement
        Route::get('get-announcement', [TeacherApiController::class, 'getAnnouncement']);
        Route::get('sent-notifications', [TeacherApiController::class, 'getSentNotifications']);
        Route::post('send-announcement', [TeacherApiController::class, 'sendAnnouncement']);
        Route::post('update-announcement', [TeacherApiController::class, 'updateAnnouncement']);
        Route::post('delete-announcement', [TeacherApiController::class, 'deleteAnnouncement']);

        Route::get('get-attendance', [TeacherApiController::class, 'getAttendance']);
        Route::post('submit-attendance', [TeacherApiController::class, 'submitAttendance']);


        //Exam
        Route::get('get-exam-list', [TeacherApiController::class, 'getExamList']); // Exam list Route
        Route::get('get-exam-details', [TeacherApiController::class, 'getExamDetails']); // Exam Details Route
        Route::post('submit-exam-marks/subject', [TeacherApiController::class, 'submitExamMarksBySubjects']); // Submit Exam Marks By Subjects Route
        Route::post('submit-exam-marks/student', [TeacherApiController::class, 'submitExamMarksByStudent']); // Submit Exam Marks By Students Route

        Route::group(['middleware' => ['auth:sanctum', 'checkStudent']], static function () {
            Route::get('get-student-result', [TeacherApiController::class, 'GetStudentExamResult']); // Student Exam Result
            Route::get('get-student-marks', [TeacherApiController::class, 'GetStudentExamMarks']); // Student Exam Marks
        });

        //Student List
        Route::get('student-list', [TeacherApiController::class, 'getStudentList']);
        Route::get('student-details', [TeacherApiController::class, 'getStudentDetails']);

        //Schedule List
        Route::get('teacher_timetable', [TeacherApiController::class, 'getTeacherTimetable']);

        Route::post('class-detail', [TeacherApiController::class, 'getClassDetail']);

        // student diaries categories
        Route::get('/diary-categories', [TeacherApiController::class, 'getStudentDiaryCategories']);
        Route::post('/create-diary-category', [TeacherApiController::class, 'createStudentDiaryCategory']);
        Route::post('/update-diary-category', [TeacherApiController::class, 'updateStudentDiaryCategory']);
        Route::post('/delete-diary-category', [TeacherApiController::class, 'deleteStudentDiaryCategory']);
        Route::post('/restore-diary-category', [TeacherApiController::class, 'restoreStudentDiaryCategory']);
        Route::post('/trash-diary-category', [TeacherApiController::class, 'trashStudentDiaryCategory']);

        // student diaries
        Route::get('/diaries', [TeacherApiController::class, 'getStudentDiaries']);
        Route::post('/create-diary', [TeacherApiController::class, 'createStudentDiary']);
        Route::post('/delete-diary', [TeacherApiController::class, 'deleteStudentDiary']);
        Route::post('/remove-student', [TeacherApiController::class, 'removeStudent']);

        // KYC
        Route::get('get-kyc-status', [\App\Http\Controllers\Api\TeacherKycController::class, 'getKycStatus']);
        Route::post('upload-kyc-document', [\App\Http\Controllers\Api\TeacherKycController::class, 'uploadDocument']);

        // Onboarding
        Route::group(['prefix' => 'onboarding'], function () {
            Route::get('jd', [\App\Http\Controllers\Api\TeacherOnboardingController::class, 'getJd']);
            Route::get('questions', [\App\Http\Controllers\Api\TeacherOnboardingController::class, 'getQuestions']);
            Route::post('submit', [\App\Http\Controllers\Api\TeacherOnboardingController::class, 'submitTest']);
        });

        // Policies
        Route::group(['prefix' => 'policies'], function () {
            Route::get('/', [\App\Http\Controllers\Api\SchoolPolicyController::class, 'getPolicies']);
            Route::post('acknowledge', [\App\Http\Controllers\Api\SchoolPolicyController::class, 'acknowledgePolicy']);
        });
    });
});


// Staff & Teacher APIs
Route::group(['prefix' => 'staff'], static function () {
    Route::post('login', [TeacherApiController::class, 'login']);
Route::get('/schools', [StaffApiController::class, 'getAllSchools']);

    Route::group(['middleware' => ['APISwitchDatabase', 'checkSchoolStatus']], static function () {
        // Payroll
        Route::get('my-payroll', [StaffApiController::class, 'myPayroll']);
        Route::get('payroll-slip', [StaffApiController::class, 'myPayrollSlip']);
        Route::post('payroll-create', [StaffApiController::class, 'storePayroll']);
        Route::get('payroll-staff-list', [StaffApiController::class, 'staffPayrollList']);

        Route::get('payroll-year', [StaffApiController::class, 'payrollYear']);
        

        Route::get('profile', [StaffApiController::class, 'profile']);
        Route::get('counter', [StaffApiController::class, 'counter']);
        Route::get('teachers', [StaffApiController::class, 'teacher']);
        Route::get('teacher-timetable', [StaffApiController::class, 'teacherTimetable']);
        Route::get('staffs', [StaffApiController::class, 'staff']);

        Route::get('leave-request', [StaffApiController::class, 'leaveRequest']);
        Route::post('leave-approve', [StaffApiController::class, 'leaveApprove']);
        Route::post('leave-delete', [StaffApiController::class, 'leaveDelete']);
        
        // Announcement
        Route::get('get-announcement', [StaffApiController::class, 'getAnnouncement']);
        Route::post('send-announcement', [StaffApiController::class, 'sendAnnouncement']);
        Route::post('update-announcement', [StaffApiController::class, 'updateAnnouncement']);
        Route::post('delete-announcement', [StaffApiController::class, 'deleteAnnouncement']);
        
        Route::get('student/attendance', [StaffApiController::class, 'studentAttendance']);

        Route::get('roles', [StaffApiController::class, 'getRoles']);
        Route::get('role-permissions/{id}', [StaffApiController::class, 'getRolePermissions']);
        Route::get('users', [StaffApiController::class, 'getUsers']);
        Route::post('notification', [StaffApiController::class, 'storeNotification']);
        Route::get('notifications', [StaffApiController::class, 'getNotification']);
        Route::post('notification-delete', [StaffApiController::class, 'deleteNotification']);
        Route::get('notification', [StaffApiController::class, 'getNotification']);
        Route::get('get-fees', [StaffApiController::class, 'getFees']);
        Route::get('fees-paid-list', [StaffApiController::class, 'getFeesPaidList']);

        Route::get('student-offline-exam-result', [StaffApiController::class, 'getOfflineExamResult']);
        Route::get('features-permission', [StaffApiController::class, 'getFeaturesPermissions']);
        
        Route::get('class-timetable', [StaffApiController::class, 'getClassTimetable']);

        Route::get('student-fees-receipt', [StaffApiController::class, 'feesReceipt']);
        Route::get('allowances-deductions', [StaffApiController::class, 'allowancesDeductions']);

        Route::post('attendance', [StaffApiController::class, 'markAttendance']);
        Route::get('attendance-history', [StaffApiController::class, 'attendanceHistory']);
        Route::get('attendance-report', [StaffApiController::class, 'attendanceReport']);
        Route::get('attendance-month-wise', [StaffApiController::class, 'attendanceMonthWiseList']);
        Route::post('attendance-month-wise', [StaffApiController::class, 'storeAttendanceMonthWise']);

        // Staff Management APIs
        Route::get('student-list', [StaffManageApiController::class, 'studentList']);
        Route::get('student-show/{id}', [StaffManageApiController::class, 'studentShow']);
        Route::delete('student-destroy/{id}', [StaffManageApiController::class, 'destroy']);
        Route::post('student-store', [StaffManageApiController::class, 'studentStore']);
        Route::post('student-update/{id}', [StaffManageApiController::class, 'studentUpdate']);
        Route::post('student-change-status/{id}', [StaffManageApiController::class, 'changeStudentStatus']);
        Route::get('admission-inquiries', [StaffManageApiController::class, 'admissionInquiries']);
        Route::post('admission-inquiry-status/{id}', [StaffManageApiController::class, 'updateApplicationStatus']);
        Route::post('update-uni', [StaffManageApiController::class, 'updateUniNo']);
        Route::post('update-profile', [StaffManageApiController::class, 'updateProfile']);
        Route::get('student-search', [StaffManageApiController::class, 'searchStudent']);
        
        Route::get('guardian-list', [StaffManageApiController::class, 'guardianList']);
        Route::post('guardian-store', [StaffManageApiController::class, 'guardianStore']);
        Route::post('guardian-update/{id}', [StaffManageApiController::class, 'guardianUpdate']);
        Route::delete('guardian-destroy/{id}', [StaffManageApiController::class, 'guardianDestroy']);
        // Slider Management APIs
        Route::get('slider-list', [StaffSliderApiController::class, 'sliderList']);
        Route::post('slider-store', [StaffSliderApiController::class, 'sliderStore']);
        Route::post('slider-update/{id}', [StaffSliderApiController::class, 'sliderUpdate']);
        Route::delete('slider-destroy/{id}', [StaffSliderApiController::class, 'sliderDestroy']);

        // Teacher Management APIs
        Route::get('teacher-list', [StaffTeacherApiController::class, 'teacherList']);
        Route::post('teacher-store', [StaffTeacherApiController::class, 'teacherStore']);
        Route::post('teacher-update/{id}', [StaffTeacherApiController::class, 'teacherUpdate']);
        Route::delete('teacher-destroy/{id}', [StaffTeacherApiController::class, 'teacherDestroy']);

        // Academics APIs
        Route::post('promote-student', [\App\Http\Controllers\PromoteStudentController::class, 'store']);
        Route::get('medium-list', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'mediumList']);
        Route::post('medium-store', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'mediumStore']);
        Route::post('medium-update/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'mediumUpdate']);
        Route::delete('medium-destroy/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'mediumDestroy']);

        Route::get('section-list', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'sectionList']);
        Route::post('section-store', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'sectionStore']);
        Route::post('section-update/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'sectionUpdate']);
        Route::delete('section-destroy/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'sectionDestroy']);

        Route::get('subject-list', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'subjectList']);
        Route::post('subject-store', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'subjectStore']);
        Route::post('subject-update/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'subjectUpdate']);
        Route::delete('subject-destroy/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'subjectDestroy']);

        Route::get('semester-list', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'semesterList']);
        Route::post('semester-store', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'semesterStore']);
        Route::post('semester-update/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'semesterUpdate']);
        Route::delete('semester-destroy/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'semesterDestroy']);

        Route::get('stream-list', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'streamList']);
        Route::post('stream-store', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'streamStore']);
        Route::post('stream-update/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'streamUpdate']);
        Route::delete('stream-destroy/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'streamDestroy']);

        Route::get('shift-list', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'shiftList']);
        Route::post('shift-store', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'shiftStore']);
        Route::post('shift-update/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'shiftUpdate']);
        Route::delete('shift-destroy/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'shiftDestroy']);

        Route::get('class-list', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'classList']);
        Route::post('class-store', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'classStore']);

        // Exam APIs
        Route::get('exam-list', [StaffExamApiController::class, 'examList']);
        Route::post('exam-store', [StaffExamApiController::class, 'examStore']);
        Route::post('exam-update/{id}', [StaffExamApiController::class, 'examUpdate']);
        Route::delete('exam-destroy/{id}', [StaffExamApiController::class, 'examDestroy']);

        Route::get('exam-timetable/{id}', [StaffExamApiController::class, 'getExamTimetable']);
        Route::post('exam-timetable/{id}', [StaffExamApiController::class, 'updateExamTimetable']);

        // Grade APIs
        Route::get('grades', [StaffGradeApiController::class, 'index']);
        Route::post('grades', [StaffGradeApiController::class, 'store']);
        Route::delete('grades/{id}', [StaffGradeApiController::class, 'destroy']);

        // Exam Result APIs
        Route::get('exam-result', [StaffExamResultApiController::class, 'showExamResult']);
        Route::post('exam-result/publish/{id}', [StaffExamResultApiController::class, 'publishExamResult']);
        Route::post('exam-result/update-marks', [StaffExamResultApiController::class, 'updateExamResultMarks']);

        // Online Exam APIs
        Route::get('online-exam', [StaffOnlineExamApiController::class, 'index']);
        Route::post('online-exam', [StaffOnlineExamApiController::class, 'store']);
        Route::post('online-exam/{id}', [StaffOnlineExamApiController::class, 'update']);
        Route::delete('online-exam/{id}', [StaffOnlineExamApiController::class, 'destroy']);

        // Online Exam Question APIs
        Route::get('online-exam-question', [StaffOnlineExamQuestionApiController::class, 'index']);
        Route::post('online-exam-question', [StaffOnlineExamQuestionApiController::class, 'store']);
        Route::post('online-exam-question/{id}', [StaffOnlineExamQuestionApiController::class, 'update']);
        Route::delete('online-exam-question/{id}', [StaffOnlineExamQuestionApiController::class, 'destroy']);

        // Online Exam Result APIs
        Route::get('online-exam-result/{id}', [StaffOnlineExamResultApiController::class, 'show']);

        // Lesson APIs
        Route::get('lesson', [StaffLessonApiController::class, 'index']);
        Route::post('lesson', [StaffLessonApiController::class, 'store']);
        Route::post('lesson/{id}', [StaffLessonApiController::class, 'update']);
        Route::delete('lesson/{id}', [StaffLessonApiController::class, 'destroy']);

        // Lesson Topic APIs
        Route::get('lesson-topic', [StaffLessonTopicApiController::class, 'index']);
        Route::post('lesson-topic', [StaffLessonTopicApiController::class, 'store']);
        Route::post('lesson-topic/{id}', [StaffLessonTopicApiController::class, 'update']);
        Route::delete('lesson-topic/{id}', [StaffLessonTopicApiController::class, 'destroy']);

        // Attendance APIs
        Route::get('attendance/data', [StaffAttendanceApiController::class, 'getAttendanceData']);
        Route::get('attendance', [StaffAttendanceApiController::class, 'show']);
        Route::post('attendance', [StaffAttendanceApiController::class, 'store']);
        Route::get('attendance/report', [StaffAttendanceApiController::class, 'attendanceReport']);

        // Leave APIs
        Route::get('leave', [StaffLeaveApiController::class, 'index']);
        Route::post('leave', [StaffLeaveApiController::class, 'store']);
        Route::post('leave/{id}', [StaffLeaveApiController::class, 'update']);
        Route::delete('leave/{id}', [StaffLeaveApiController::class, 'destroy']);
        
        Route::get('leave-request', [StaffLeaveApiController::class, 'leaveRequests']);
        Route::post('leave-status', [StaffLeaveApiController::class, 'updateLeaveStatus']);
        Route::get('leave-report', [StaffLeaveApiController::class, 'leaveReport']);
        Route::get('leave-master', [StaffLeaveApiController::class, 'leaveMasterIndex']);
        Route::post('leave-master', [StaffLeaveApiController::class, 'leaveMasterStore']);
        Route::post('leave-master/{id}', [StaffLeaveApiController::class, 'leaveMasterUpdate']);
        Route::delete('leave-master/{id}', [StaffLeaveApiController::class, 'leaveMasterDestroy']);

        // Fees APIs
        Route::get('fees-class', [StaffFeesApiController::class, 'show']);
        Route::post('fees-class', [StaffFeesApiController::class, 'store']);
        Route::post('fees-class/{id}', [StaffFeesApiController::class, 'update']);
        Route::delete('fees-class/{id}', [StaffFeesApiController::class, 'destroy']);
        Route::post('fees-paid/compulsory', [StaffFeesApiController::class, 'compulsoryFeesPaidStore']);
        Route::post('fees-paid/optional', [StaffFeesApiController::class, 'optionalFeesPaidStore']);

        // Assignment/Homework APIs
        Route::get('assignment', [StaffAssignmentApiController::class, 'show']);
        Route::post('assignment', [StaffAssignmentApiController::class, 'store']);
        Route::post('assignment/{id}', [StaffAssignmentApiController::class, 'update']);
        Route::delete('assignment/{id}', [StaffAssignmentApiController::class, 'destroy']);
        Route::get('assignment-submission/{id}', [StaffAssignmentApiController::class, 'showAssignmentSubmissionDetails']);
        Route::post('assignment-submission/evaluate', [StaffAssignmentApiController::class, 'bulkAssignmentSubmissionUpdate']);

        // Announcement APIs
        Route::get('announcement', [StaffAnnouncementApiController::class, 'index']);
        Route::post('announcement', [StaffAnnouncementApiController::class, 'store']);
        Route::post('announcement/{id}', [StaffAnnouncementApiController::class, 'update']);
        Route::delete('announcement/{id}', [StaffAnnouncementApiController::class, 'destroy']);

        // Gallery APIs
        Route::get('gallery', [StaffGalleryApiController::class, 'index']);
        Route::post('gallery', [StaffGalleryApiController::class, 'store']);
        Route::delete('gallery/{id}', [StaffGalleryApiController::class, 'destroy']);

        Route::post('class-update/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'classUpdate']);
        Route::delete('class-destroy/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'classDestroy']);

        Route::get('class-section-list', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'classSectionList']);
        Route::post('class-section-store', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'classSectionStore']);
        Route::post('class-section-update/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'classSectionUpdate']);
        Route::delete('class-section-destroy/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'classSectionDestroy']);

        Route::post('class-teacher-assign', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'classTeacherAssign']);
        Route::delete('class-teacher-remove/{id}', [\App\Http\Controllers\Api\StaffAcademicsApiController::class, 'classTeacherRemove']);
        
        Route::post('reset-password', [StaffManageApiController::class, 'resetPassword']);

        // Holidays
        Route::get('holidays', [\App\Http\Controllers\Api\HolidayApiController::class, 'index']);
        Route::post('holidays', [\App\Http\Controllers\Api\HolidayApiController::class, 'store']);
        Route::put('holidays/{id}', [\App\Http\Controllers\Api\HolidayApiController::class, 'update']);
        Route::delete('holidays/{id}', [\App\Http\Controllers\Api\HolidayApiController::class, 'destroy']);

        // Session Years
        Route::get('session-years', [\App\Http\Controllers\Api\SessionYearApiController::class, 'index']);
        Route::post('session-years', [\App\Http\Controllers\Api\SessionYearApiController::class, 'store']);
        Route::put('session-years/{id}', [\App\Http\Controllers\Api\SessionYearApiController::class, 'update']);
        Route::delete('session-years/{id}', [\App\Http\Controllers\Api\SessionYearApiController::class, 'destroy']);

        // Timetable
        Route::get('timetable', [\App\Http\Controllers\Api\TimetableApiController::class, 'index']);
        Route::post('timetable', [\App\Http\Controllers\Api\TimetableApiController::class, 'store']);
        Route::delete('timetable/{id}', [\App\Http\Controllers\Api\TimetableApiController::class, 'destroy']);

        // Student Pickup Verification
        Route::post('verify-student-pickup-otp', [StudentPickupController::class, 'verifyPickupOTP']);
        Route::get('student-pickup-requests', [StudentPickupController::class, 'getAllPickupRequests']);
    });
});

/**
 * GENERAL APIs
 **/
Route::get('settings', [ApiController::class, 'getSettings']);
Route::post('forgot-password', [ApiController::class, 'forgotPassword']);

Route::get('school-details',[ApiController::class, 'schoolDetails']);

// Route::group(['middleware' => ['auth:sanctum',]], static function () {
Route::group(['middleware' => ['APISwitchDatabase',]], static function () {
    Route::get('school-settings', [ApiController::class, 'getSchoolSettings']);
    Route::get('holidays', [ApiController::class, 'getHolidays']);
    Route::post('change-password', [ApiController::class, 'changePassword']);
//    Route::get('test', [ApiController::class, 'getPaymentMethod']);
    Route::get('payment-confirmation', [ApiController::class, 'getPaymentConfirmation'])->name('payment-confirmation');
    Route::get('payment-transactions', [ApiController::class, 'getPaymentTransactions'])->name('payment-transactions');
    Route::get('payment-upi-details', [ApiController::class, 'getUpiDetails'])->name('payment-upi-details');
    Route::post('payment-manual-upi', [ApiController::class, 'storeManualUpiTransaction'])->name('payment-manual-upi');
    Route::get('gallery', [ApiController::class, 'getGallery']);
    Route::get('session-years', [ApiController::class, 'getSessionYear']);
//    Route::get('features', [ApiController::class, 'getFeatures']);

    // Leaves
    Route::get('leaves', [ApiController::class, 'getLeaves']);
    Route::post('leaves', [ApiController::class, 'applyLeaves']);
    Route::get('my-leaves', [ApiController::class, 'getMyLeaves']);
    Route::post('delete-my-leaves', [ApiController::class, 'deleteLeaves']);
    Route::get('staff-leaves-details', [ApiController::class, 'getStaffLeaveDetail']);
    Route::get('leave-settings', [ApiController::class, 'leaveSettings']);

    Route::get('medium', [ApiController::class, 'getMedium']);
    Route::get('classes', [ApiController::class, 'getClass']);

    Route::post('update-profile', [ApiController::class, 'updateProfile']);
    Route::get('student-exan-result-pdf', [ApiController::class, 'getExamResultPdf']);

    Route::post('message', [ApiController::class, 'sendMessage']);
    Route::get('message', [ApiController::class, 'getMessage']);
    Route::post('delete/message', [ApiController::class, 'deleteMessage']);
    Route::post('message/read', [ApiController::class, 'readMessage']);

    // Get users from role
    // Student, Teacher, Guardian, Other Staff [Teachers / School Staff]
    // Get all users
    Route::get('users', [ApiController::class, 'getUsers']);

    // Get history
    Route::get('users/chat/history', [ApiController::class, 'usersChatHistory']);

    Route::post('class-section/teachers', [ApiController::class, 'classSectionTeachers']);
    
    Route::get('student-details', [ApiController::class, 'getStudentDetails']);
});

/**
 * CONSENT FORM APIs (Staff / Principal)
 **/
Route::group(['prefix' => 'consent-form', 'middleware' => ['auth:sanctum', 'APISwitchDatabase']], static function () {
    Route::get('/', [\App\Http\Controllers\Api\ConsentFormApiController::class, 'index']);
});

/**
 * SUPER ADMIN APIs
 **/
Route::group(['prefix' => 'super-admin', 'middleware' => ['APISwitchDatabase']], static function () {
    Route::group(['prefix' => 'audit'], static function () {
        Route::get('questions', [\App\Http\Controllers\Api\SuperAdminAuditController::class, 'getQuestions']);
        Route::post('questions', [\App\Http\Controllers\Api\SuperAdminAuditController::class, 'storeQuestion']);
        Route::put('questions/{id}', [\App\Http\Controllers\Api\SuperAdminAuditController::class, 'updateQuestion']);
        Route::delete('questions/{id}', [\App\Http\Controllers\Api\SuperAdminAuditController::class, 'deleteQuestion']);

        Route::get('school-audits', [\App\Http\Controllers\Api\SuperAdminAuditController::class, 'getSchoolAudits']);
        Route::post('school-audits', [\App\Http\Controllers\Api\SuperAdminAuditController::class, 'storeSchoolAudit']);
        Route::get('school-audits/{id}', [\App\Http\Controllers\Api\SuperAdminAuditController::class, 'getSchoolAuditDetails']);
    });
});

/**
 * APP ADMIN APIs
 **/
Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'APISwitchDatabase']], static function () {
    // Session Years
    Route::get('session-years', [SessionYearApiController::class, 'index']);
    Route::post('session-years', [SessionYearApiController::class, 'store']);
    Route::put('session-years/{id}', [SessionYearApiController::class, 'update']);
    Route::delete('session-years/{id}', [SessionYearApiController::class, 'destroy']);

    // Holidays
    Route::get('holidays', [HolidayApiController::class, 'index']);
    Route::post('holidays', [HolidayApiController::class, 'store']);
    Route::put('holidays/{id}', [HolidayApiController::class, 'update']);
    Route::delete('holidays/{id}', [HolidayApiController::class, 'destroy']);

    // Timetable
    Route::get('timetable', [TimetableApiController::class, 'index']);
    Route::post('timetable', [TimetableApiController::class, 'store']);
    Route::delete('timetable/{id}', [TimetableApiController::class, 'destroy']);
});
