<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolAudit;
use Carbon\Carbon;

class ArchiveOldAudits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:archive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive completed audits that are older than 3 months';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threeMonthsAgo = Carbon::now()->subMonths(3);

        $audits = SchoolAudit::where('status', 1)
            ->whereNull('archived_at')
            ->where('submission_date', '<=', $threeMonthsAgo)
            ->get();

        $count = 0;
        foreach ($audits as $audit) {
            $audit->archived_at = now();
            $audit->save();
            $count++;
        }

        $this->info("Successfully archived {$count} audits.");
    }
}
