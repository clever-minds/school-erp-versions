<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class RollbackAllTenants extends Command
{
    protected $signature = 'tenants:rollback';
    protected $description = 'Rollback specific migrations on all tenant databases';

    public function handle()
    {
        $tenants = DB::table('schools')->get();

        foreach ($tenants as $tenant) {
            $this->info("Rolling back tenant: {$tenant->name}");

            // ✅ Tenant DB config
            Config::set('database.connections.tenant', [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => $tenant->database_name,
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', 'Lcischool@Cm91011'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => false,
            ]);

            DB::purge('tenant');
            DB::reconnect('tenant');

            // 🔌 Test connection
            try {
                DB::connection('tenant')->getPdo();
                $this->info("✅ Connected: {$tenant->database_name}");
            } catch (\Exception $e) {
                $this->error("❌ Connection failed: {$e->getMessage()}");
                continue;
            }

            try {

                Artisan::call('migrate:rollback', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_29_133406_add_extra_fields_to_students_table.php',
                    '--force' => true,
                ]);

                $this->info("✅ Rollback done for {$tenant->name}");
            } catch (\Exception $e) {
                $this->error("❌ Rollback failed for {$tenant->name}: {$e->getMessage()}");
            }
        }

        $this->info('🎉 All tenant rollbacks completed successfully!');
    }
}
