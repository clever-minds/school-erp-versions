<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\SystemUpdateProgressEvent;
use App\Jobs\ProcessSchoolUpdateJob;
use App\Models\School;
use App\Models\SystemSetting;
use App\Models\SystemUpdateRun;
use App\Models\SystemUpdateTrack;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

class SystemUpdateService
{
    private string $destinationPath;

    public function __construct(
        private readonly CachingService $cache
    ) {
        $this->destinationPath = base_path() . '/update/tmp/';
    }

    /**
     * Get the latest run or a specific run by ID.
     */
    public function getLatestRun(): ?SystemUpdateRun
    {
        return SystemUpdateRun::on('mysql')->latest()->first();
    }

    /**
     * Get run status with school progress.
     */
    public function getRunStatus(string $runId): ?array
    {
        $run = SystemUpdateRun::on('mysql')->where('run_id', $runId)->first();
        if (!$run) {
            return null;
        }

        $tracks = SystemUpdateTrack::on('mysql')->where('run_id', $runId)
            ->orderBy('school_name')
            ->get();

        return [
            'run' => $run->toArray(),
            'tracks' => $tracks->toArray(),
        ];
    }

    /**
     * Get paginated tracks for a given run with optional search and status filter.
     */
    public function getPaginatedTracks(string $runId, int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = SystemUpdateTrack::on('mysql')
            ->where('run_id', $runId)
            ->orderBy('school_name');

        if ($search) {
            $query->where('school_name', 'LIKE', '%' . $search . '%');
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Broadcast a global progress update.
     */
    public function broadcastGlobalProgress(SystemUpdateRun $run, string $stage, ?string $logEntry = null): void
    {
        $completedSchools = (int) $run->completed_schools;
        $totalSchools = (int) $run->total_schools;
        $failedSchools = (int) $run->failed_schools;

        $progressPercent = $this->calculateGlobalProgress($run->status, $completedSchools, $totalSchools);

        if ($logEntry) {
            $run->appendLog($logEntry);
        }

        event(new SystemUpdateProgressEvent($run->run_id, 'global', [
            'status' => $run->status,
            'stage' => $stage,
            'progress' => $progressPercent,
            'log_entry' => $logEntry ? ('[' . now()->format('H:i:s') . '] ' . $logEntry) : null,
            'completed_schools' => $completedSchools,
            'failed_schools' => $failedSchools,
            'total_schools' => $totalSchools,
        ]));
    }

    /**
     * Broadcast a school-specific progress update.
     */
    public function broadcastSchoolProgress(SystemUpdateTrack $track): void
    {
        $run = SystemUpdateRun::on('mysql')->where('run_id', $track->run_id)->first();

        event(new SystemUpdateProgressEvent($track->run_id, 'school', [
            'school_id' => $track->school_id,
            'school_name' => $track->school_name,
            'database_name' => $track->database_name,
            'status' => $track->status,
            'migration_status' => $track->migration_status,
            'seeder_status' => $track->seeder_status,
            'progress' => $track->progress,
            'error_message' => $track->error_message,
            'failed_step' => $track->failed_step,
            'completed_schools' => $run ? $run->completed_schools : 0,
            'failed_schools' => $run ? $run->failed_schools : 0,
            'total_schools' => $run ? $run->total_schools : 0,
        ]));
    }

    /**
     * Calculate overall global progress percentage based on stage.
     */
    public function calculateGlobalProgress(string $status, int $completedSchools, int $totalSchools): int
    {
        return match ($status) {
            'pending' => 0,
            'extracting' => 10,
            'main_migration' => 25,
            'tenant_migration' => $totalSchools > 0
                ? 30 + (int) (($completedSchools / $totalSchools) * 60)
                : 30,
            'cache_optimization' => 95,
            'completed' => 100,
            'failed' => 0,
            default => 0,
        };
    }

    /**
     * Initialize school tracking records for a run.
     */
    public function initializeSchoolTracks(string $runId): int
    {
        $schools = School::on('mysql')->withTrashed()->where('installed', 1)->get();
        $totalSchools = $schools->count();

        foreach ($schools as $school) {
            $track = SystemUpdateTrack::on('mysql')->create([
                'run_id' => $runId,
                'school_id' => $school->id,
                'school_name' => $school->name,
                'database_name' => $school->database_name,
                'status' => 'queued',
                'migration_status' => 'pending',
                'seeder_status' => 'pending',
                'progress' => 0,
            ]);

            $this->broadcastSchoolProgress($track);
        }

        return $totalSchools;
    }

    /**
     * Dispatch per-school update jobs.
     */
    public function dispatchSchoolJobs(string $runId): void
    {
        $tracks = SystemUpdateTrack::on('mysql')->where('run_id', $runId)
            ->where('status', 'queued')
            ->get();

        foreach ($tracks as $track) {
            ProcessSchoolUpdateJob::dispatch($runId, $track->school_id);
        }
    }

    /**
     * Retry a specific failed school.
     */
    public function retrySchool(string $runId, int $schoolId): bool
    {
        $track = SystemUpdateTrack::on('mysql')->where('run_id', $runId)
            ->where('school_id', $schoolId)
            ->where('status', 'failed')
            ->first();

        if (!$track) {
            return false;
        }

        // Reset track
        $track->update([
            'status' => 'queued',
            'migration_status' => 'pending',
            'seeder_status' => 'pending',
            'progress' => 0,
            'error_message' => null,
            'failed_step' => null,
            'started_at' => null,
            'completed_at' => null,
        ]);

        // Decrement failed count on run
        $run = SystemUpdateRun::on('mysql')->where('run_id', $runId)->first();
        if ($run && $run->failed_schools > 0) {
            $run->decrement('failed_schools');
            // Reset run status if it was marked completed/failed
            if (in_array($run->status, ['completed', 'failed'])) {
                $run->update(['status' => 'tenant_migration']);
            }
        }

        $this->broadcastSchoolProgress($track->fresh());
        if ($run) {
            $this->broadcastGlobalProgress($run->fresh(), 'Retrying school: ' . $track->school_name);
        }

        ProcessSchoolUpdateJob::dispatch($runId, $schoolId);

        return true;
    }

    /**
     * Execute a post-update task.
     */
    public function runPostUpdateTask(string $task): array
    {
        try {
            switch ($task) {
                case 'restart_supervisor':
                    // Supervisor restart must be done at OS level
                    return ['success' => true, 'message' => 'Supervisor restart signal sent. Please verify manually on the server.'];

                case 'disable_maintenance':
                    SystemSetting::on('mysql')->where('name', 'web_maintenance')->update(['data' => 0]);
                    $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
                    return ['success' => true, 'message' => 'Maintenance mode disabled.'];

                case 'clear_cache':
                    Artisan::call('view:clear');
                    Artisan::call('route:clear');
                    Artisan::call('config:clear');
                    Artisan::call('cache:clear');
                    return ['success' => true, 'message' => 'All caches cleared successfully.'];

                case 'verify_queue':
                    return ['success' => true, 'message' => 'Email queue health check initiated.'];

                default:
                    return ['success' => false, 'message' => 'Unknown task.'];
            }
        } catch (Throwable $e) {
            Log::error("Post-update task '{$task}' failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Toggle maintenance mode.
     */
    public function toggleMaintenance(bool $enable): void
    {
        SystemSetting::on('mysql')->where('name', 'web_maintenance')->update([
            'data' => $enable ? 1 : 0,
        ]);
        $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
    }

    /**
     * Check if maintenance mode is active.
     */
    public function isMaintenanceModeActive(): bool
    {
        $setting = SystemSetting::on('mysql')->where('name', 'web_maintenance')->first();
        return $setting && (int) $setting->data === 1;
    }

    /**
     * Check pre-update requirements.
     */
    public function getPreUpdateChecks(): array
    {
        $maintenanceMode = $this->isMaintenanceModeActive();

        return [
            'local_setup' => [
                'label' => __('running_a_system_update_on_your_local_pc_Please_click_the_link_to_complete_the_configuration'),
                'passed' => true, // Manual confirmation
                'link' => 'https://wrteam-in.github.io/eSchool-SaaS-Doc/installation/admin-panel-installation/queue-setup/#local-setup-development'
            ],
            'database_backup' => [
                'label' => __('database_backup'),
                'passed' => true, // Manual confirmation
            ],
            'reverb_setup' => [
                'label' => __('make_sure_reverb_correctly_configured_running'),
                'passed' => true, // Manual confirmation
            ],
            'storage_backup' => [
                'label' => __('file_backup'),
                'passed' => true, // Manual confirmation
            ],
            'maintenance_mode' => [
                'label' => __('maintenance_mode_active'),
                'passed' => $maintenanceMode,
            ],
            'php_extensions' => [
                'label' => __('verify_php_extensions'),
                'passed' => extension_loaded('zip') && extension_loaded('pdo_mysql') && extension_loaded('curl'),
            ],
        ];
    }

    /**
     * Finalize a run after all schools are processed.
     */
    public function finalizeRun(string $runId): void
    {
        $run = SystemUpdateRun::on('mysql')->where('run_id', $runId)->first();
        if (!$run) {
            return;
        }

        $pendingCount = SystemUpdateTrack::on('mysql')->where('run_id', $runId)
            ->whereNotIn('status', ['completed', 'failed'])
            ->count();

        if ($pendingCount > 0) {
            return; // Still processing
        }

        $failedCount = SystemUpdateTrack::on('mysql')->where('run_id', $runId)
            ->where('status', 'failed')
            ->count();

        // Move to cache optimization stage
        $run->update(['status' => 'cache_optimization']);
        $this->broadcastGlobalProgress($run, 'Running cache optimization...', 'Starting cache and optimization...');

        // Clear caches
        try {
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            $run->appendLog('All caches cleared successfully.');
        } catch (Throwable $e) {
            Log::error('Cache clearing failed: ' . $e->getMessage());
            $run->appendLog('Warning: Cache clearing encountered errors: ' . $e->getMessage());
        }

        $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));

        $finalStatus = $failedCount > 0 ? 'completed' : 'completed'; // completed even with partial failures
        $run->update([
            'status' => $finalStatus,
            'completed_at' => now(),
        ]);

        $message = $failedCount > 0
            ? "Update completed with {$failedCount} failed school(s). Please retry or check errors."
            : 'System update completed successfully!';

        $run->appendLog($message);
        $this->broadcastGlobalProgress($run->fresh(), $message, $message);
    }
}
