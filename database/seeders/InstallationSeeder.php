<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Language;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstallationSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {

        /**** Create All the Permission ****/
        $this->createPermissions();

        $this->createSuperAdminRole();

        $this->createSchoolAdminRole();

//        $this->createTeacherRole();


        // System Features
        $this->systemFeatures();

        Role::updateOrCreate(['name' => 'School Admin']);

        //Change system version here

        SystemSetting::upsert([
            ["id" => 1, "name" => "time_zone", "data" => "Asia/Kolkata", "type" => "string"],
            ["id" => 2, "name" => "date_format", "data" => "d-m-Y", "type" => "date"],
            ["id" => 3, "name" => "time_format", "data" => "h:i A", "type" => "time"],
            ["id" => 4, "name" => "theme_color", "data" => "#22577A", "type" => "string"],
            ["id" => 5, "name" => "session_year", "data" => 1, "type" => "string"],
            ["id" => 6, "name" => "system_version", "data" => "1.0.1", "type" => "string"],
            ["id" => 7, "name" => "email_verified", "data" => 0, "type" => "boolean"],
            ["id" => 8, "name" => "subscription_alert", "data" => 7, "type" => "integer"],
            ["id" => 9, "name" => "currency_code", "data" => "USD", "type" => "string"],
            ["id" => 10, "name" => "currency_symbol", "data" => "$", "type" => "string"],
            ["id" => 11, "name" => "additional_billing_days", "data" => "5", "type" => "integer"],
            ["id" => 12, "name" => "system_name", "data" => "eSchool Saas", "type" => "string"],
            ["id" => 13, "name" => "address", "data" => "#262-263, Time Square Empire, SH 42 Mirjapar highway, Bhuj - Kutch 370001 Gujarat India.", "type" => "string"],
            ["id" => 14, "name" => "billing_cycle_in_days", "data" => "30", "type" => "integer"],
            ["id" => 15, "name" => "current_plan_expiry_warning_days", "data" => "7", "type" => "integer"],
            ["id" => 16, "name" => "front_site_theme_color", "data" => "#e9f9f3", "type" => "text"],
            ["id" => 17, "name" => "primary_color", "data" => "#3ccb9b", "type" => "text"],
            ["id" => 18, "name" => "secondary_color", "data" => "#245a7f", "type" => "text"],
            ["id" => 19, "name" => "short_description", "data" => "eSchool-Saas - Manage Your School", "type" => "text"],
            ["id" => 20, "name" => "facebook", "data" => "https://www.facebook.com/wrteam.in/", "type" => "text"],
            ["id" => 21, "name" => "instagram", "data" => "https://www.instagram.com/wrteam.in/", "type" => "text"],
            ["id" => 22, "name" => "linkedin", "data" => "https://in.linkedin.com/company/wrteam", "type" => "text"],
            ["id" => 23, "name" => "footer_text", "data" => "<p>&copy;&nbsp;<strong><a href='https://wrteam.in/' target='_blank' rel='noopener noreferrer'>WRTeam</a></strong>. All Rights Reserved</p>", "type" => "text"],

        ], ['id'], ['name', 'data', 'type']);

        Language::updateOrCreate(['id' => 1], ['name' => 'English', 'code' => 'en', 'file' => 'en.json', 'status' => 1, 'is_rtl' => 0]);
        //clear cache
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
    }


    public function createPermissions() {

        $permissions = [
            ...self::permission('role'),
            ...self::permission('medium'),
            ...self::permission('section'),
            ...self::permission('class'),
            ...self::permission('class-section'),
            ...self::permission('subject'),
            ...self::permission('teacher'),
            ...self::permission('guardian'),
            ...self::permission('session-year'),
            ...self::permission('student'),
            ...self::permission('timetable'),
            ...self::permission('attendance'),
            ...self::permission('holiday'),
            ...self::permission('announcement'),
            ...self::permission('slider'),
            ...self::permission('promote-student'),
            ...self::permission('language'),
            ...self::permission('lesson'),
            ...self::permission('topic'),
            ...self::permission('schools'),
            ...self::permission('form-fields'),
            ...self::permission('grade'),
            ...self::permission('package'),
            ...self::permission('addons'),


            ...self::permission('assignment'),
            ['name' => 'assignment-submission'],

            ...self::permission('exam'),
            ...self::permission('exam-timetable'),


            ['name' => 'exam-upload-marks'],
            ['name' => 'exam-result'],

            ['name' => 'system-setting-manage'],
            ['name' => 'fcm-setting-create'],
            ['name' => 'email-setting-create'],
            ['name' => 'privacy-policy'],
            ['name' => 'contact-us'],
            ['name' => 'about-us'],
            ['name' => 'terms-condition'],

            ['name' => 'class-teacher'],
            ['name' => 'student-reset-password'],
            ['name' => 'reset-password-list'],
            ['name' => 'student-change-password'],

            ['name' => 'update-admin-profile'],

            ['name' => 'fees-classes'],
            ['name' => 'fees-paid'],
            ['name' => 'fees-config'],

            ['name' => 'school-setting-manage'],
            ['name' => 'app-settings'],
            ['name' => 'subscription-view'],

            ...self::permission('online-exam'),
            ...self::permission('online-exam-questions'),
            ...self::permission('fees-type'),
            ...self::permission('fees-class'),
            ...self::permission('role'),
            ...self::permission('staff'),
            ...self::permission('expense-category'),
            ...self::permission('expense'),
            ...self::permission('semester'),
            ...self::permission('payroll'),
            ...self::permission('stream'),
            ...self::permission('shift'),
            // ...self::permission('leave'),
            ...self::permission('faqs'),

            ['name' => 'online-exam-result-list'],

            // ['name' => 'approve-leave'],

            ['name' => 'fcm-setting-manage'],
            ['name' => 'front-site-setting'],

            ...self::permission('fees'),
            ...self::permission('transfer-student'),
        ];
        $permissions = array_map(static function ($data) {
            $data['guard_name'] = 'web';
            return $data;
        }, $permissions);
        Permission::upsert($permissions, ['name'], ['name']);
    }


    public function createSuperAdminRole() {
        $role = Role::withoutGlobalScope('school')->updateOrCreate(['name' => 'Super Admin', 'custom_role' => 0, 'editable' => 0]);
        $superadminHasAccessTo = [
            'schools-list',
            'schools-create',
            'schools-edit',
            'schools-delete',

            'package-list',
            'package-create',
            'package-edit',
            'package-delete',

            'email-setting-create',
            'privacy-policy',
            'terms-condition',
            'contact-us',
            'about-us',
            'fcm-setting-create',
            'language-list',
            'language-create',
            'language-edit',
            'language-delete',
            'system-setting-manage',
            'app-settings',

            'role-list',
            'role-create',
            'role-edit',
            'role-delete',

            'staff-list',
            'staff-create',
            'staff-edit',
            'staff-delete',

            'addons-list',
            'addons-create',
            'addons-edit',
            'addons-delete',

            'subscription-view',

            'faqs-list',
            'faqs-create',
            'faqs-edit',
            'faqs-delete',

            'fcm-setting-manage',

            'front-site-setting',

            'update-admin-profile'

        ];
        $role->syncPermissions($superadminHasAccessTo);
    }


    public function createSchoolAdminRole() {
        $role = Role::withoutGlobalScope('school')->updateOrCreate(['name' => 'School Admin', 'custom_role' => 0, 'editable' => 0]);
        $SchoolAdminHasAccessTo = [
            'medium-list',
            'medium-create',
            'medium-edit',
            'medium-delete',

            'section-list',
            'section-create',
            'section-edit',
            'section-delete',

            'class-list',
            'class-create',
            'class-edit',
            'class-delete',

            'class-section-list',
            'class-section-create',
            'class-section-edit',
            'class-section-delete',

            'subject-list',
            'subject-create',
            'subject-edit',
            'subject-delete',

            'teacher-list',
            'teacher-create',
            'teacher-edit',
            'teacher-delete',

            'guardian-list',
            'guardian-create',
            'guardian-edit',
            'guardian-delete',

            'session-year-list',
            'session-year-create',
            'session-year-edit',
            'session-year-delete',

            'student-list',
            'student-create',
            'student-edit',
            'student-delete',

            'timetable-list',
            'timetable-create',
            'timetable-edit',
            'timetable-delete',

            'attendance-list',

            'holiday-list',
            'holiday-create',
            'holiday-edit',
            'holiday-delete',

            'announcement-list',
            'announcement-create',
            'announcement-edit',
            'announcement-delete',

            'slider-list',
            'slider-create',
            'slider-edit',
            'slider-delete',

            'exam-create',
            'exam-list',
            'exam-edit',
            'exam-delete',

            'assignment-submission',

            'student-reset-password',
            'reset-password-list',
            'student-change-password',

            'promote-student-list',
            'promote-student-create',
            'promote-student-edit',
            'promote-student-delete',

            'transfer-student-list',
            'transfer-student-create',
            'transfer-student-edit',
            'transfer-student-delete',

            'update-admin-profile',

            'fees-paid',
            'fees-config',

            'form-fields-list',
            'form-fields-create',
            'form-fields-edit',
            'form-fields-delete',

            'grade-create',
            'grade-list',
            'grade-edit',
            'grade-delete',

            'exam-timetable-create',
            'exam-timetable-list',
            'exam-timetable-delete',

            'school-setting-manage',

            'fees-type-list',
            'fees-type-create',
            'fees-type-edit',
            'fees-type-delete',

            'fees-class-list',
            'fees-class-create',
            'fees-class-edit',
            'fees-class-delete',

            'online-exam-result-list',
            'exam-result',

            'role-list',
            'role-create',
            'role-edit',
            'role-delete',

            'staff-list',
            'staff-create',
            'staff-edit',
            'staff-delete',

            'expense-category-list',
            'expense-category-create',
            'expense-category-edit',
            'expense-category-delete',

            'expense-list',
            'expense-create',
            'expense-edit',
            'expense-delete',

            'fees-list',
            'fees-create',
            'fees-edit',
            'fees-delete',

            'semester-list',
            'semester-create',
            'semester-edit',
            'semester-delete',

            'payroll-list',
            'payroll-create',
            'payroll-edit',
            'payroll-delete',

            'stream-list',
            'stream-create',
            'stream-edit',
            'stream-delete',

            'shift-list',
            'shift-create',
            'shift-edit',
            'shift-delete',
            'online-exam-list'

            // 'approve-leave',

        ];

        $role->syncPermissions($SchoolAdminHasAccessTo);
    }

    public function createTeacherRole() {
        //Add Teacher Role
        $teacher_role = Role::updateOrCreate(['name' => 'Teacher']);
        $TeacherHasAccessTo = [
            'student-list',
            'timetable-list',
            'attendance-list',
            'attendance-create',
            'attendance-edit',
            'attendance-delete',
            'holiday-list',
            'announcement-list',
            'announcement-create',
            'announcement-edit',
            'announcement-delete',
            'assignment-create',
            'assignment-list',
            'assignment-edit',
            'assignment-delete',
            'assignment-submission',
            'lesson-list',
            'lesson-create',
            'lesson-edit',
            'lesson-delete',
            'topic-list',
            'topic-create',
            'topic-edit',
            'topic-delete',
            'class-section-list',
            'online-exam-create',
            'online-exam-list',
            'online-exam-edit',
            'online-exam-delete',
            'online-exam-questions-create',
            'online-exam-questions-list',
            'online-exam-questions-edit',
            'online-exam-questions-delete',
            'online-exam-result-list',
            // 'leave-list',
            // 'leave-create',
            // 'leave-edit',
            // 'leave-delete',
        ];
        $teacher_role->syncPermissions($TeacherHasAccessTo);
    }


    /**
     * Generate List , Create , Edit , Delete Permissions
     * @param $prefix
     * @param array $customPermissions - Prefix will be set Automatically
     * @return string[]
     */
    public static function permission($prefix, array $customPermissions = []) {

        $list = [["name" => $prefix . '-list']];
        $create = [["name" => $prefix . '-create']];
        $edit = [["name" => $prefix . '-edit']];
        $delete = [["name" => $prefix . '-delete']];

        $finalArray = array_merge($list, $create, $edit, $delete);
        foreach ($customPermissions as $customPermission) {
            $finalArray[] = ["name" => $prefix . "-" . $customPermission];
        }
        return $finalArray;
    }

    // System Features
    public function systemFeatures() {
        $features = [
            ['name' => 'Student Management', 'is_default' => 1],
            ['name' => 'Academics Management', 'is_default' => 1],
            ['name' => 'Slider Management', 'is_default' => 0],
            ['name' => 'Teacher Management', 'is_default' => 1],
            ['name' => 'Session Year Management', 'is_default' => 1],
            ['name' => 'Holiday Management', 'is_default' => 0],
            ['name' => 'Timetable Management', 'is_default' => 0],
            ['name' => 'Attendance Management', 'is_default' => 0],
            ['name' => 'Exam Management', 'is_default' => 0],
            ['name' => 'Lesson Management', 'is_default' => 0],
            ['name' => 'Assignment Management', 'is_default' => 0],
            ['name' => 'Announcement Management', 'is_default' => 0],
            ['name' => 'Staff Management', 'is_default' => 0],
            ['name' => 'Expense Management', 'is_default' => 0],
        ];

        foreach ($features as $key => $feature) {
            Feature::updateOrCreate(['id' => ($key + 1)], $feature);
        }
    }
}
