<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddTeacherInterviewUpdateStatusPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Ye seeder 'teacher-interview-update-status' permission create karta hai
     * aur use Super Admin role ko assign karta hai.
     *
     * Live server pe run karo:
     * php artisan db:seed --class=AddTeacherInterviewUpdateStatusPermissionSeeder
     */
    public function run(): void
    {
        $permissionName = 'teacher-interview-update-status';

        // Permission create karo agar pehle se exist nahi karti
        $permission = Permission::firstOrCreate(['name' => $permissionName]);

        $this->command->info("Permission '{$permissionName}' created/verified.");

        // Super Admin ko ye permission do
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            if (!$superAdmin->hasPermissionTo($permissionName)) {
                $superAdmin->givePermissionTo($permission);
                $this->command->info("Permission assigned to 'Super Admin' role.");
            } else {
                $this->command->info("'Super Admin' already has this permission.");
            }
        } else {
            $this->command->warn("'Super Admin' role not found!");
        }

        // Spatie permission cache clear karo
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info("Permission cache cleared. Done!");
    }
}
