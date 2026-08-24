<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For tenant context, we need to create the permission
        $permission = Permission::upsert(
            [
                ['name' => 'manual-upi-transaction-list', 'guard_name' => 'web'],
            ],
            ['name'],
            ['name']
        );

        $role = Role::where('name', 'School Admin')->first();
        if ($role) {
            $role->givePermissionTo('manual-upi-transaction-list');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = Role::where('name', 'School Admin')->first();
        if ($role) {
            $role->revokePermissionTo('manual-upi-transaction-list');
        }
        Permission::where('name', 'manual-upi-transaction-list')->delete();
    }
};
