<?php

namespace App\Jobs;

use App\Events\AcademySetupProgressUpdated;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Services\AcademySetupWizardService;
use App\Services\CachingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessAcademySetupWizardJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly int $schoolId,
        public readonly int $userId,
        public readonly string $runId,
        public readonly array $payload
    ) {}

    public function handle(AcademySetupWizardService $setupService): void
    {
        $school = School::on('mysql')->findOrFail($this->schoolId);
        $this->bootSchoolConnection($school->database_name);

        $progress = $setupService->initializeRun($this->schoolId, $this->runId);
        event(new AcademySetupProgressUpdated($this->schoolId, $this->runId, $progress));

        $setupService->process($this->schoolId, $this->payload, function (string $checkpoint, string $status, array $meta = []) use (&$progress) {
            if (isset($progress['checkpoints'][$checkpoint])) {
                $progress['checkpoints'][$checkpoint]['status'] = $status;
            }

            $isCompleted = collect($progress['checkpoints'])->every(fn($row) => ($row['status'] ?? 'pending') === 'done');
            $progress['status'] = $isCompleted ? 'completed' : 'processing';
            $progress['message'] = $isCompleted ? 'Academy setup completed successfully' : ('Completed ' . str_replace('_', ' ', $checkpoint));
            if (!empty($meta)) {
                $progress['meta'] = $meta;
            }

            SchoolSetting::on('school')->updateOrCreate(
                ['school_id' => $this->schoolId, 'name' => 'academy_setup_progress'],
                ['data' => json_encode($progress), 'type' => 'string']
            );

            // clear school cache
            $cache = app(CachingService::class);
            $cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'), $this->schoolId);

            $cache->removeSchoolCache(config("constants.CACHE.SCHOOL.SESSION_YEAR"), $this->schoolId);
            $cache->removeSchoolCache(config("constants.CACHE.SCHOOL.SEMESTER"), $this->schoolId);
            $cache->removeSchoolCache(config("constants.CACHE.SCHOOL.ALL_SESSION_YEARS"), $this->schoolId);

            event(new AcademySetupProgressUpdated($this->schoolId, $this->runId, $progress));
        });

        // clear school cache
        $cache = app(CachingService::class);
        $cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'), $this->schoolId);
    }

    public function failed(Throwable $exception): void
    {
        $school = School::on('mysql')->find($this->schoolId);
        if ($school) {
            $this->bootSchoolConnection($school->database_name);
        }

        $previousProgressRaw = SchoolSetting::on('school')
            ->where('school_id', $this->schoolId)
            ->where('name', 'academy_setup_progress')
            ->value('data');

        $previousProgress = json_decode((string) $previousProgressRaw, true);
        if (!is_array($previousProgress)) {
            $previousProgress = [
                'checkpoints' => [],
            ];
        }

        $progress = [
            'status' => 'failed',
            'message' => $exception->getMessage(),
            'checkpoints' => $previousProgress['checkpoints'] ?? [],
        ];

        SchoolSetting::on('school')->updateOrCreate(
            ['school_id' => $this->schoolId, 'name' => 'academy_setup_in_progress'],
            ['data' => '0', 'type' => 'string']
        );

        SchoolSetting::on('school')->updateOrCreate(
            ['school_id' => $this->schoolId, 'name' => 'academy_setup_progress'],
            ['data' => json_encode($progress), 'type' => 'string']
        );

        event(new AcademySetupProgressUpdated($this->schoolId, $this->runId, $progress));
    }

    private function bootSchoolConnection(string $databaseName): void
    {
        config(['database.connections.school.database' => $databaseName]);
        DB::purge('school');
        DB::connection('school')->reconnect();
        DB::setDefaultConnection('school');
    }
}
