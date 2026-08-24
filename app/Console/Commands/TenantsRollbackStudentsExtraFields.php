<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class TenantsRollbackStudentsExtraFields extends Command
{
    protected $signature = 'tenants:rollback-students-extra';
    protected $description = 'Force rollback extra student fields for all tenant databases';

    public function handle()
    {
        $tenants = DB::table('schools')->get();

        foreach ($tenants as $tenant) {
            $this->info("🔄 Rolling back tenant: {$tenant->name}");

            // Tenant DB config
            Config::set('database.connections.tenant', [
                'driver' => 'mysql',
                'host' => env('DB_HOST'),
                'port' => env('DB_PORT'),
                'database' => $tenant->database_name,
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ]);

            DB::purge('tenant');
            DB::reconnect('tenant');

            try {
                DB::connection('tenant')->getPdo();

                Schema::connection('tenant')->table('students', function (Blueprint $table) {

                    $columns = [
                        'blood_group',
                        'nationality',
                        'birth_place',
                        'last_school',
                        'last_cleared_class',
                        'education_board',
                        'remarks',
                    ];

                    foreach ($columns as $column) {
                        if (Schema::connection('tenant')->hasColumn('students', $column)) {
                            $table->dropColumn($column);
                        }
                    }
                });

                // (Optional) remove migration entry if exists
                DB::connection('tenant')
                    ->table('migrations')
                    ->where('migration', '2025_12_29_133406_add_extra_fields_to_students_table')
                    ->delete();

                $this->info("✅ Rollback success: {$tenant->name}");

            } catch (\Exception $e) {
                $this->error("❌ Rollback failed for {$tenant->name}: {$e->getMessage()}");
            }
        }

        $this->info('🎉 Rollback completed for all tenants');
    }
}
