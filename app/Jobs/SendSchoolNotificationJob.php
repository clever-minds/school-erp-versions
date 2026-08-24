<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Holiday;
use App\Models\Reminder;
use App\Models\Schedule;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendSchoolNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $schoolId,
        private string $date,
        private string $timeframe
    ) {}

    public function handle()
    {
        try {
            // Ensure we start on central connection to fetch school details
            DB::setDefaultConnection('mysql');
            $school = School::findOrFail($this->schoolId);

            // Configure and switch to tenant database
            Config::set('database.connections.school.database', $school->database_name);
            DB::purge('school');
            DB::connection('school')->reconnect();
            DB::setDefaultConnection('school');

            $this->processNotifications($school);

        } catch (\Exception $e) {
            Log::error("Job failed for school ID {$this->schoolId}: " . $e->getMessage());
            throw $e;
        } finally {
            // Reset to avoid side effects
            DB::setDefaultConnection('mysql');
        }
    }

    private function processNotifications($school)
    {
        // Common recipients: Staff, Teachers, and Guardians (Parents)
        $userIds = User::where('school_id', $school->id)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['Staff', 'Teacher', 'Guardian']);
            })
            ->pluck('id')
            ->toArray();

        if (empty($userIds)) return;

        $note = "\n\nNote: Event or schedule dates may change as per situation, so cooperation is required.";

        // 1. Events
        $events = Event::where('date', $this->date)->get();
        foreach ($events as $event) {
            $this->sendToAll($school, $userIds, $event, "Upcoming Event: {$event->title}", "Event '{$event->title}' is coming up on " . Carbon::parse($event->date)->format('d-m-Y') . "." . $note);
        }

        // 2. Holidays
        $holidays = Holiday::where('date', $this->date)->get();
        foreach ($holidays as $holiday) {
            $this->sendToAll($school, $userIds, $holiday, "Upcoming Holiday: {$holiday->title}", "School will be closed for '{$holiday->title}' on " . Carbon::parse($holiday->date)->format('d-m-Y') . "." . $note);
        }

        // 3. Schedules
        $schedules = Schedule::where('date', $this->date)->get();
        foreach ($schedules as $schedule) {
            $this->sendToAll($school, $userIds, $schedule, "Upcoming Schedule: {$schedule->title}", "Schedule '{$schedule->title}' is set for " . Carbon::parse($schedule->date)->format('d-m-Y') . "." . $note);
        }

        // 4. Reminders
        $reminders = Reminder::where('date', $this->date)->get();
        foreach ($reminders as $reminder) {
            $this->sendToAll($school, $userIds, $reminder, "Reminder: {$reminder->title}", "Reminder for '{$reminder->title}' on " . Carbon::parse($reminder->date)->format('d-m-Y') . "." . $note);
        }
    }

    private function sendToAll($school, $userIds, $model, $title, $message)
    {
        $mockModel = (object)[
            'title' => $title,
            'message' => $message
        ];

        // Ensure we use the helper from the specific database context
        send_notification_all($mockModel, $userIds, 'Notification', [
            'school_id' => $school->id, 
            'event_date' => $model->date
        ]);
        
        Log::info("Sent: {$title} to " . count($userIds) . " users for school {$school->name}");
    }
}
