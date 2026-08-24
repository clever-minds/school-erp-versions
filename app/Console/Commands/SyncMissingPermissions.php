<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class SyncMissingPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync-missing {school_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync missing permissions to main database and school databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $permissions = [
            'assign-roll-no',
            'contact-us',
            'generate-staff-id-card',
            'generate-student-id-card',
            'login-as-staff',
            'staff-attendance-create',
            'staff-attendance-delete',
            'staff-attendance-edit',
            'staff-attendance-list',
            'event-list',
            'event-create',
            'event-edit',
            'event-delete',
            'manage-expense-list',
            'manage-expense-show',
            'reminder-list',
            'reminder-create',
            'reminder-edit',
            'reminder-delete',
            'school-policy-list',
            'school-policy-create',
            'school-policy-edit',
            'school-policy-delete',
            'student-pickup-list',
            'student-pickup-create',
            'student-pickup-edit',
            'student-pickup-delete',
            'staff-kyc-upload',
            'manual-upi-transaction-list'
        ];

        $schoolId = $this->argument('school_id');

        $this->info("Starting permissions sync...");

        // 1. Sync to main (mysql) database
        DB::setDefaultConnection('mysql');
        foreach ($permissions as $name) {
            Permission::updateOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $this->info("Permissions synced to main database.");

        // 2. Sync to tenant databases
        $query = School::on('mysql');
        if ($schoolId) {
            $query->where('id', $schoolId);
        }
        $schools = $query->get();

        if ($schools->isEmpty()) {
            $this->error("No valid schools found.");
            return;
        }

        foreach ($schools as $school) {
            try {
                $this->info("Syncing for school: {$school->name} (DB: {$school->database_name})");
                
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::setDefaultConnection('school');

                $mapped = array_map(function($p) { return ['name' => $p, 'guard_name' => 'web']; }, $permissions);
                Permission::upsert($mapped, ['name'], ['name']);
                
                // Sync School Admin Role
                app(\App\Services\SchoolDataService::class)->createSchoolAdminRole($school);

                // Sync Teacher Role
                app(\App\Services\SchoolDataService::class)->createTeacherRole($school);

                // Create and sync other staff roles (Principal, Accountant, etc.)
                app(\App\Services\SchoolDataService::class)->createStaffRoles($school);

                // Forget cached permissions
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            } catch (\Exception $e) {
                $this->error("Failed for school {$school->database_name}: " . $e->getMessage());
            }
        }

        // Revert to mysql default
        DB::setDefaultConnection('mysql');
        
        $this->info("All done! Missing permissions and roles have been synced.");
    }
}
