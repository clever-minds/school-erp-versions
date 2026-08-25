<?php

namespace App\Jobs;

use App\Models\School;
use App\Services\SessionYearMigrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SessionYearMigrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;
    public int $backoff = 120; // 2 minute between retries

    /**
     * Create a new job instance.
     *
     * @param int $schoolId
     * @param int $fromSessionYearId
     * @param int $toSessionYearId
     * @param array $migrateOptions
     * @param array|null $semesterData
     */
    public function __construct(
        private readonly int $schoolId,
        private readonly int $fromSessionYearId,
        private readonly int $toSessionYearId,
        private readonly array $migrateOptions,
        private readonly ?array $semesterData = null
    ) {}

    /**
     * Execute the job.
     *
     * @param SessionYearMigrationService $migrationService
     * @return void
     */
    public function handle(SessionYearMigrationService $migrationService): void
    {
        try {
            $school = School::on('mysql')->findOrFail($this->schoolId);

            if ($school->database_name) {
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::setDefaultConnection('school');
            }

            Log::info("Starting session year migration for School ID: {$this->schoolId} from {$this->fromSessionYearId} to {$this->toSessionYearId}");

            $migrationService->migrate(
                $this->fromSessionYearId,
                $this->toSessionYearId,
                $this->migrateOptions,
                $this->schoolId,
                $this->semesterData
            );

            Log::info("Session year migration completed for School ID: {$this->schoolId}");
        } catch (Throwable $e) {
            Log::error("Session year migration failed for school ID: {$this->schoolId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::error("Session year migration job failed permanently for school ID: {$this->schoolId}", [
            'error' => $exception->getMessage()
        ]);
    }
}
