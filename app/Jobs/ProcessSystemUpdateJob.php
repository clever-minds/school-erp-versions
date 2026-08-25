<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\SystemSetting;
use App\Models\SystemUpdateRun;
use App\Services\CachingService;
use App\Services\SystemUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;
use ZipArchive;

class ProcessSystemUpdateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes for main operations
    public int $backoff = 60;

    private string $destinationPath;

    public function __construct(
        public readonly string $runId,
        public readonly string $filePath
    ) {
        $this->destinationPath = base_path() . '/update/tmp/';
    }

    public function handle(): void
    {
        $run = SystemUpdateRun::on('mysql')->where('run_id', $this->runId)->firstOrFail();
        $service = app(SystemUpdateService::class);

        // ── STAGE 1: File Extraction ──
        $run->update(['status' => 'extracting', 'started_at' => now()]);
        $run->appendLog('License pre-verified. Ok.');
        $service->broadcastGlobalProgress($run, 'File Extraction', 'License pre-verified. Starting file extraction...');

        $versionFile = $this->extractFiles($service, $run);

        // Restart Queue
        $run->update(['status' => 'queue_restart']);
        $service->broadcastGlobalProgress($run, 'Queue Restart', 'Restarting Queue...');
        Artisan::call('queue:restart');
        sleep(5);

        // ── STAGE 2: Main Database Migration ──
        $run->update(['status' => 'main_migration']);
        $service->broadcastGlobalProgress($run, 'Main Database', 'Running migrations: Main DB...');

        Artisan::call('migrate', ['--force' => true]);
        $run->appendLog('Main database migration completed.');
        $service->broadcastGlobalProgress($run, 'Main Database', 'Main database migration completed.');

        Artisan::call('db:seed', ['--class' => 'InstallationSeeder', '--force' => true]);
        $run->appendLog('Installation seeder completed.');
        $service->broadcastGlobalProgress($run, 'Main Database', 'Installation seeder completed.');

        // Cleanup temp files
        $this->cleanup();

        // Update system version (after cleanup, before tenant jobs finish)
        if (isset($versionFile['update_version'])) {
            SystemSetting::on('mysql')->where('name', 'system_version')->update([
                'data' => $versionFile['update_version'],
            ]);
            $run->update(['version_to' => $versionFile['update_version']]);
            $run->appendLog('System version updated to ' . $versionFile['update_version']);
        }

        // ── STAGE 3: Tenant Migrations ──
        $run->update(['status' => 'tenant_migration']);
        $service->broadcastGlobalProgress($run, 'Tenant Migrations', 'Starting Tenant loop (Count: ...)...');

        $totalSchools = $service->initializeSchoolTracks($this->runId);
        $run->update(['total_schools' => $totalSchools]);
        $run->appendLog("Starting Tenant loop (Count: {$totalSchools})...");
        $service->broadcastGlobalProgress($run->fresh(), 'Tenant Migrations', "Initialized {$totalSchools} school tracking records.");

        if ($totalSchools === 0) {
            // No schools, skip to cache
            $service->finalizeRun($this->runId);
        } else {
            // Dispatch per-school jobs
            $service->dispatchSchoolJobs($this->runId);
        }
    }


    /**
     * Extract the uploaded zip file.
     *
     * @return array Version file contents
     */
    private function extractFiles(SystemUpdateService $service, SystemUpdateRun $run): array
    {
        $targetPath = base_path() . DIRECTORY_SEPARATOR;

        // Extract outer zip
        $zip = new ZipArchive();
        $res = $zip->open($this->filePath);
        if ($res !== true) {
            throw new \RuntimeException("Failed to open uploaded zip file. Error code: {$res}");
        }
        $zip->extractTo($this->destinationPath);
        $zip->close();

        $run->appendLog('Unpacking system files into /srv/www/html/...');
        $service->broadcastGlobalProgress($run, 'File Extraction', 'Unpacking system files...');

        $verFile = $this->destinationPath . 'version_info.php';
        $sourcePath = $this->destinationPath . 'source_code.zip';

        if (!file_exists($verFile) && !file_exists($sourcePath)) {
            throw new \RuntimeException('Zip file structure is invalid: missing version_info.php or source_code.zip');
        }

        $verFileDest = $targetPath . 'version_info.php';
        $sourcePathDest = $targetPath . 'source_code.zip';

        if (!rename($verFile, $verFileDest) || !rename($sourcePath, $sourcePathDest)) {
            throw new \RuntimeException('Error occurred while moving zip files.');
        }

        $versionFile = require($verFileDest);
        $currentVersion = SystemSetting::on('mysql')->where('name', 'system_version')->first()?->data ?? '0.0.0';

        // Version compatibility check
        if ($currentVersion !== ($versionFile['update_version'] ?? null)) {
            if ($currentVersion !== ($versionFile['current_version'] ?? null)) {
                @unlink($verFileDest);
                @unlink($sourcePathDest);
                throw new \RuntimeException("{$currentVersion} - Please update nearest version first.");
            }
        }

        // Extract source code
        $zip2 = new ZipArchive();
        if ($zip2->open($sourcePathDest) !== true) {
            @unlink($verFileDest);
            @unlink($sourcePathDest);
            throw new \RuntimeException('Source code zip extraction failed.');
        }
        $zip2->extractTo($targetPath);
        $zip2->close();

        @unlink($sourcePathDest);
        @unlink($verFileDest);

        $run->appendLog('File extraction completed successfully.');
        $service->broadcastGlobalProgress($run, 'File Extraction', 'File extraction completed.');

        return $versionFile;
    }

    /**
     * Cleanup temp files.
     */
    private function cleanup(): void
    {
        if (is_dir($this->destinationPath)) {
            $files = glob($this->destinationPath . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($this->destinationPath);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error("System update job failed for run {$this->runId}: " . $exception->getMessage(), [
            'trace' => $exception->getTraceAsString(),
        ]);

        $run = SystemUpdateRun::on('mysql')->where('run_id', $this->runId)->first();
        if ($run) {
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);
            $run->appendLog('FATAL: ' . $exception->getMessage());

            $service = app(SystemUpdateService::class);
            $service->broadcastGlobalProgress($run->fresh(), 'Update Failed', 'FATAL: ' . $exception->getMessage());
        }

        $this->cleanup();
    }
}
