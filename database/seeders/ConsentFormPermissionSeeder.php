<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ConsentFormPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'consent-form-list',
            'consent-form-create',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $schoolAdmin = Role::where('name', 'School Admin')->first();
        if ($schoolAdmin) {
            $schoolAdmin->givePermissionTo($permissions);
        }



        // Backfill existing students with their parent's account creation date
        \Illuminate\Support\Facades\DB::statement("
            UPDATE students
            JOIN users ON students.guardian_id = users.id
            SET students.consent_form_date = users.created_at
            WHERE students.consent_form_date IS NULL
        ");
    }
}
