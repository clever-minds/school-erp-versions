<?php

use App\Models\School;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissionName = 'staff-attendance-list';
        
        // Get all schools/tenants
        $tenants = School::all();

        foreach ($tenants as $tenant) {
            try {
                $tenantDbName = $tenant->database_name;

                if (empty($tenantDbName)) {
                    continue;
                }

                // Switch DB connection to tenant
                config(['database.connections.school.database' => $tenantDbName]);
                DB::purge('school');
                DB::reconnect('school');

                // Clear spatie permission cache for this tenant
                app()[PermissionRegistrar::class]->forgetCachedPermissions();

                // Ensure the permission exists in the tenant database
                $permission = Permission::on('school')->firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);

                // Get all roles in this tenant
                $roles = Role::on('school')->get();

                foreach ($roles as $role) {
                    // Assign permission to role
                    // We use givePermissionTo on the model which is on the 'school' connection
                    $role->givePermissionTo($permission);
                }

                // Clear cache again after update
                app()[PermissionRegistrar::class]->forgetCachedPermissions();

            } catch (\Exception $e) {
                // Log error if needed, but continue for other tenants
                \Log::error("Failed to assign staff-attendance-list for tenant {$tenant->name}: " . $e->getMessage());
            }
        }

        // Switch back to main DB just in case
        DB::purge('school');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy rollback for cross-tenant permission assignment
    }
};
