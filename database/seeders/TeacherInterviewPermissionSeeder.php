<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TeacherInterviewPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'teacher-interview-manage',
            'teacher-interview-list',
            'teacher-interview-create',
            'teacher-interview-edit',
            'teacher-interview-delete',
            'teacher-interview-update-status',
            'teacher-interview-question-list',
            'teacher-interview-question-create',
            'teacher-interview-question-edit',
            'teacher-interview-question-delete',
            'assigned-teacher-interview',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Cleanup old permission if it exists
        $oldPermission = Permission::where('name', 'teacher-interview-my')->first();
        if ($oldPermission) {
            $oldPermission->delete();
        }

        // Automatically assign to Super Admin
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // Revoke all teacher-interview permissions from School Admin and Teacher, then grant only assigned-teacher-interview
        $schoolAdmin = Role::where('name', 'School Admin')->first();
        if ($schoolAdmin) {
            $schoolAdmin->revokePermissionTo($permissions);
            $schoolAdmin->givePermissionTo('assigned-teacher-interview');
        }

        $teacher = Role::where('name', 'Teacher')->first();
        if ($teacher) {
            $teacher->revokePermissionTo($permissions);
            $teacher->givePermissionTo('assigned-teacher-interview');
        }
    }
}
