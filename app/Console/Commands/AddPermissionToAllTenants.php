<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddPermissionToAllTenants extends Command
{
    protected $signature = 'tenants:add-permission {permissions*}';
    protected $description = 'Add and assign permissions to the School Admin role in all tenant (school) databases';

    public function handle()
    {
        $permissions = $this->argument('permissions');
        $this->info("🚀 Starting to add permissions to all tenants...");

        // Get all tenants from main DB
        $tenants = DB::connection('mysql')->table('schools')->get();

        if ($tenants->isEmpty()) {
            $this->error('❌ No tenants found in schools table.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->info("\n🎓 Processing school: {$tenant->name}");

            try {
                $tenantDbName = $tenant->database_name ?? $tenant->database ?? $tenant->db_name;

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

                // Ensure School Admin role exists
                $adminRole = Role::on('school')->firstOrCreate(
                    ['name' => 'School Admin', 'guard_name' => 'web']
                );

                foreach ($permissions as $permissionName) {
                    // Create or get permission
                    $permission = Permission::on('school')->firstOrCreate([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                    ]);

                    // 🔁 Force refresh Spatie cache
                    app()[PermissionRegistrar::class]->forgetCachedPermissions();
                    app()[PermissionRegistrar::class]->registerPermissions();

                    // Reload role after cache reset
                    $adminRole = Role::on('school')->where('name', 'School Admin')->first();

                    // ✅ Assign permission safely after confirming existence
                    $exists = Permission::on('school')
                        ->where('name', $permissionName)
                        ->where('guard_name', 'web')
                        ->exists();

                    if (!$exists) {
                        $this->error("❌ Permission '{$permissionName}' not found in DB after creation for {$tenant->name}");
                        continue;
                    }

                    // Give permission
                    try {
                        $adminRole->givePermissionTo($permission);
                        $this->info("✅ '{$permissionName}' assigned to 'School Admin' in {$tenant->name}");
                    } catch (\Exception $e) {
                        $this->error("⚠️ Failed assigning '{$permissionName}' in {$tenant->name}: " . $e->getMessage());
                    }
                }

                // Final cache cleanup per tenant
                app()[PermissionRegistrar::class]->forgetCachedPermissions();

            } catch (\Exception $e) {
                $this->error("❌ Error for tenant {$tenant->name}: " . $e->getMessage());
            }
        }

        $this->info("\n🎉 All permissions created and assigned successfully to 'School Admin' in all tenants!");
    }
}
