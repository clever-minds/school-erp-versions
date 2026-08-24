<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class StaffAttendanceQrTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissionName = 'staff-attendance-qr';

        $this->command->info("🚀 Starting to add '{$permissionName}' to all existing schools (tenants)...");

        // Get all tenants from the main database
        $tenants = DB::connection('mysql')->table('schools')->get();

        if ($tenants->isEmpty()) {
            $this->command->error('❌ No tenants found in schools table.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->command->info("\n🎓 Processing school: {$tenant->name}");

            try {
                $tenantDbName = $tenant->database_name ?? $tenant->database ?? $tenant->db_name;

                if (empty($tenantDbName)) {
                    $this->command->error("❌ No database name found for school {$tenant->name}");
                    continue;
                }

                // Switch DB to tenant
                config(['database.connections.school.database' => $tenantDbName]);
                DB::purge('school');
                DB::reconnect('school');

                // Clear old permission cache
                app()[PermissionRegistrar::class]->forgetCachedPermissions();

                // Ensure School Admin role exists
                $adminRole = Role::on('school')->firstOrCreate(
                    ['name' => 'School Admin', 'guard_name' => 'web']
                );

                // Create or get permission
                $permission = Permission::on('school')->firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);

                // Force refresh Spatie cache
                app()[PermissionRegistrar::class]->forgetCachedPermissions();
                app()[PermissionRegistrar::class]->registerPermissions();

                // Reload role after cache reset
                $adminRole = Role::on('school')->where('name', 'School Admin')->first();

                // Assign permission
                try {
                    $adminRole->givePermissionTo($permission);
                    $this->command->info("✅ '{$permissionName}' assigned to 'School Admin' in {$tenant->name}");
                } catch (\Exception $e) {
                    $this->command->error("⚠️ Failed assigning '{$permissionName}' in {$tenant->name}: " . $e->getMessage());
                }

                // Final cache cleanup per tenant
                app()[PermissionRegistrar::class]->forgetCachedPermissions();

            } catch (\Exception $e) {
                $this->command->error("❌ Error for tenant {$tenant->name}: " . $e->getMessage());
            }
        }

        $this->command->info("\n🎉 All permissions created and assigned successfully to 'School Admin' in all existing schools!");
    }
}
