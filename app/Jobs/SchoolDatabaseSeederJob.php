<?php

namespace App\Jobs;

use App\Models\School;
use App\Services\SchoolDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SchoolDatabaseSeederJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public int $timeout = 300; // 5 minutes timeout
    public int $backoff = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly School $school
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->school->database_name) {
            $schoolService = app(SchoolDataService::class);
            Config::set('database.connections.school.database', $this->school->database_name);
            DB::purge('school');
            DB::connection('school')->reconnect();
            DB::setDefaultConnection('school');

            $schoolService->createPermissions();
            $schoolService->createSchoolAdminRole($this->school);
            $schoolService->createTeacherRole($this->school);
            $schoolService->createDriverRole($this->school);
            $schoolService->createHelperRole($this->school);
            $schoolService->defaultRoles($this->school);
            $schoolService->createPayrollSettingsSeeder($this->school);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("School database seeder job failed for school ID: {$this->school->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
