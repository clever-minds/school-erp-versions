<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssignStaffAttendancePermission extends Command
{
    protected $signature = 'tenants:assign-attendance-permission {school_id?}';
    protected $description = 'Assign staff-attendance permissions to ALL roles in ALL tenant databases (or a specific school)';

    public function handle()
    {
        $permissionNames = ['staff-attendance-list', 'staff-attendance-create', 'staff-attendance-edit', 'staff-attendance-delete'];
        $schoolId = $this->argument('school_id');

        if ($schoolId) {
            $tenants = DB::connection('mysql')->table('schools')->where('id', $schoolId)->get();
            $this->info("🚀 Starting to assign staff attendance permissions to ALL roles in School ID: {$schoolId}...");
        } else {
            $tenants = DB::connection('mysql')->table('schools')->get();
            $this->info("🚀 Starting to assign staff attendance permissions to ALL roles in ALL tenants...");
        }

        if ($tenants->isEmpty()) {
            $this->error('❌ No tenants found in schools table.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->info("\n🎓 Processing school: {$tenant->name}");

            try {
                $tenantDbName = $tenant->database_name;

                if (empty($tenantDbName)) {
                    $this->error("❌ No database name found for school {$tenant->name}");
                    continue;
                }

                // Switch DB to tenant
                config(['database.connections.school.database' => $tenantDbName]);
                DB::purge('school');
                DB::reconnect('school');

                // Clear old permission cache
                app()[PermissionRegistrar::class]->forgetCachedPermissions();

                foreach ($permissionNames as $permissionName) {
                    // Ensure permission exists in tenant DB
                    $permission = Permission::on('school')->firstOrCreate([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                    ]);

                    // Get ALL roles in this tenant
                    $roles = Role::on('school')->get();

                    foreach ($roles as $role) {
                        try {
                            $role->givePermissionTo($permission);
                            $this->info("✅ Assigned '{$permissionName}' to role: '{$role->name}'");
                        } catch (\Exception $e) {
                            $this->error("⚠️ Failed assigning '{$permissionName}' to '{$role->name}': " . $e->getMessage());
                        }
                    }
                }

                // Final cache cleanup per tenant
                app()[PermissionRegistrar::class]->forgetCachedPermissions();

            } catch (\Exception $e) {
                $this->error("❌ Error for tenant {$tenant->name}: " . $e->getMessage());
            }
        }

        $this->info("\n🎉 Task completed successfully!");
    }
}
