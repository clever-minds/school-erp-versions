<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use App\Models\Holiday;
use App\Models\Reminder;
use App\Models\Schedule;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-scheduled';
    protected $description = 'Send notifications for events, holidays, schedules, and reminders 1 week and 1 day before.';

    public function handle()
    {
        // Get schools from main DB
        DB::setDefaultConnection('mysql');
        $schools = School::where('status', 1)->get();
        
        $date_7_days = Carbon::now()->addDays(7)->format('Y-m-d');
        $date_1_day = Carbon::now()->addDay()->format('Y-m-d');

        foreach ($schools as $school) {
            $this->info("Dispatching notifications for school: {$school->name}");
            
            // Dispatch background jobs
            \App\Jobs\SendSchoolNotificationJob::dispatch($school->id, $date_7_days, '1 week');
            \App\Jobs\SendSchoolNotificationJob::dispatch($school->id, $date_1_day, '1 day');
        }

        $this->info("All notification jobs dispatched to worker.");
        return Command::SUCCESS;
    }
}
