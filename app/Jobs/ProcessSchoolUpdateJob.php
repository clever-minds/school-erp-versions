<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\SystemUpdateRun;
use App\Models\SystemUpdateTrack;
use App\Services\CachingService;
use App\Services\SchoolDataService;
use App\Services\SystemUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessSchoolUpdateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes per school
    public int $backoff = 60;

    public function __construct(
        public readonly string $runId,
        public readonly int $schoolId
    ) {}

    public function handle(): void
    {
        // Ensure we start on the main connection for tracking queries
        DB::setDefaultConnection('mysql');

        $track = SystemUpdateTrack::on('mysql')->where('run_id', $this->runId)
            ->where('school_id', $this->schoolId)
            ->firstOrFail();

        $school = School::on('mysql')->withTrashed()->findOrFail($this->schoolId);
        $service = app(SystemUpdateService::class);

        // Mark as started
        $track->update([
            'status' => 'migrating',
            'migration_status' => 'running',
            'progress' => 10,
            'started_at' => now(),
        ]);
        $service->broadcastSchoolProgress($track);

        // ── Run School Migrations ──
        $this->bootSchoolConnection($school->database_name);

        try {
            Artisan::call('migrate', [
                '--database' => 'school',
                '--path' => 'database/migrations/schools',
                '--force' => true,
            ]);

            $track->update([
                'migration_status' => 'completed',
                'progress' => 50,
            ]);
            $service->broadcastSchoolProgress($track);

            // ── Run School Seeders ──
            $track->update([
                'status' => 'seeding',
                'seeder_status' => 'running',
                'progress' => 60,
            ]);
            $service->broadcastSchoolProgress($track);

            $schoolService = app(SchoolDataService::class);
            $schoolService->createPermissions();
            $schoolService->createSchoolAdminRole($school);
            $schoolService->createTeacherRole($school);

            // Check for additional role methods (from SchoolDatabaseSeederJob)
            if (method_exists($schoolService, 'createDriverRole')) {
                $schoolService->createDriverRole($school);
            }
            if (method_exists($schoolService, 'createHelperRole')) {
                $schoolService->createHelperRole($school);
            }
            if (method_exists($schoolService, 'defaultRoles')) {
                $schoolService->defaultRoles($school);
            }
            if (method_exists($schoolService, 'createPayrollSettingsSeeder')) {
                $schoolService->createPayrollSettingsSeeder($school);
            }

            // Update academy setup status
            SchoolSetting::on('school')->upsert([
                'school_id' => $school->id,
                'name' => 'academy_setup_status',
                'data' => 1,
            ], ['school_id', 'name'], ['data']);

            $cache = app(CachingService::class);
            $cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'), $school->id);
        } finally {
            // CRITICAL: Always reset default connection back to main database
            // Without this, any model without explicit ->on('mysql') queries school DB
            // causing "packages table not found" and similar errors
            DB::setDefaultConnection('mysql');
            DB::purge('school');
        }

        // ── Mark Completed ──
        $track->update([
            'status' => 'completed',
            'seeder_status' => 'completed',
            'progress' => 100,
            'completed_at' => now(),
        ]);
        $service->broadcastSchoolProgress($track);

        // Update run counters
        $run = SystemUpdateRun::on('mysql')->where('run_id', $this->runId)->first();
        if ($run) {
            $run->increment('completed_schools');
            $run->appendLog("School '{$school->name}' (ID: {$school->id}) updated successfully.");
            $service->broadcastGlobalProgress(
                $run->fresh(),
                'Tenant Migrations',
                "School '{$school->name}' completed."
            );

            // Check if all schools are done
            $this->checkRunCompletion();
        }
    }

    public function failed(Throwable $exception): void
    {
        // Ensure we're on the main connection for tracking queries
        DB::setDefaultConnection('mysql');
        DB::purge('school');

        Log::error("School update failed for school ID {$this->schoolId}, run {$this->runId}: " . $exception->getMessage(), [
            'trace' => $exception->getTraceAsString(),
        ]);

        $track = SystemUpdateTrack::on('mysql')->where('run_id', $this->runId)
            ->where('school_id', $this->schoolId)
            ->first();

        if ($track) {
            $failedStep = $track->migration_status === 'completed' ? 'seeder' : 'migration';
            $failedStatusField = $failedStep === 'migration' ? 'migration_status' : 'seeder_status';

            $track->update([
                'status' => 'failed',
                $failedStatusField => 'failed',
                'error_message' => $exception->getMessage(),
                'failed_step' => $failedStep,
                'completed_at' => now(),
            ]);

            $service = app(SystemUpdateService::class);
            $service->broadcastSchoolProgress($track);

            // Update run counters
            $run = SystemUpdateRun::on('mysql')->where('run_id', $this->runId)->first();
            if ($run) {
                $run->increment('failed_schools');
                $run->appendLog("FAILED: School ID {$this->schoolId} - {$exception->getMessage()}");
                $service->broadcastGlobalProgress(
                    $run->fresh(),
                    'Tenant Migrations',
                    "FAILED: School ID {$this->schoolId}"
                );

                $this->checkRunCompletion();
            }
        }
    }

    private function bootSchoolConnection(string $databaseName): void
    {
        Config::set('database.connections.school.database', $databaseName);
        DB::purge('school');
        DB::connection('school')->reconnect();
        DB::setDefaultConnection('school');
    }

    /**
     * Check if all schools in the run are done and finalize if so.
     */
    private function checkRunCompletion(): void
    {
        $pendingCount = SystemUpdateTrack::on('mysql')->where('run_id', $this->runId)
            ->whereNotIn('status', ['completed', 'failed'])
            ->count();

        if ($pendingCount === 0) {
            $service = app(SystemUpdateService::class);
            $service->finalizeRun($this->runId);
        }
    }
}
