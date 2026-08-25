<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeleteOldBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-old-backups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete database backup zip files older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $directories = [
            'database-backup/schools',
            'database-backup/super-admin'
        ];

        $deletedCount = 0;

        foreach ($directories as $directory) {
            if (Storage::exists($directory)) {
                $subDirectories = Storage::directories($directory);

                foreach ($subDirectories as $subDir) {
                    $files = Storage::files($subDir);

                    foreach ($files as $file) {
                        if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                            $lastModified = Storage::lastModified($file);

                            if (Carbon::createFromTimestamp($lastModified)->lessThan(now()->subDay())) {
                                Storage::delete($file);
                                $deletedCount++;
                            }
                        }
                    }
                }
            }
        }

        $this->info("Deleted {$deletedCount} old backup files.");
        Log::info("DeleteOldBackups Command: Deleted {$deletedCount} old backup files.");

        return 0;
    }
}
