<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuditPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Audit Questions permissions
            'audit-question-list',
            'audit-question-create',
            'audit-question-edit',
            'audit-question-delete',
            
            // School Audits permissions
            'school-audit-list',
            'school-audit-create',
            'school-audit-edit',
            'school-audit-delete',
        ];

        // Create the permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Automatically assign these permissions to Super Admin if role exists
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }
}
