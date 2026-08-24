<?php

namespace App\Console\Commands;

use App\Models\Leave;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendLeaveReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send 3 reminder notifications to principals for teacher leave/absence (at T-3, T-2, T-1 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schools = School::all();

        foreach ($schools as $school) {
            $this->info("Processing school: {$school->name}");

            try {
                // Switch database connection
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::reconnect('school');
                DB::setDefaultConnection('school');

                // Check for approved leaves starting in 1, 2, or 3 days
                $reminderDays = [1, 2, 3];

                foreach ($reminderDays as $days) {
                    $targetDate = Carbon::now()->addDays($days)->format('Y-m-d');
                    
                    $leaves = Leave::whereDate('from_date', $targetDate)
                        ->where('status', 1) // Approved
                        ->with('user')
                        ->get();

                    foreach ($leaves as $leave) {
                        $this->sendToPrincipals($leave, $days, $school->id);
                    }
                }

            } catch (Throwable $e) {
                $this->error("Error processing school {$school->name}: " . $e->getMessage());
            }
        }

        $this->info('Leave reminders check completed.');
    }

    private function sendToPrincipals($leave, $days, $schoolId)
    {
        // Find users with 'approve-leave' permission (Principals/Admins)
        // Note: Using 'school' connection which is already set as default
        $principalIds = User::whereHas('roles.permissions', function ($q) {
            $q->where('name', 'approve-leave');
        })->pluck('id')->toArray();

        if (empty($principalIds)) {
            $this->warn("No principals found for school ID: $schoolId");
            return;
        }

        $teacherName = $leave->user->full_name;
        $dateStr = $leave->from_date;
        $title = "";
        $body = "";

        if ($days == 1) {
            $title = "Urgent Leave Reminder: $teacherName (Tomorrow)";
            $body = "Teacher $teacherName will be on leave tomorrow ($dateStr). Please check arrangements.";
        } else {
            $title = "Leave Reminder: $teacherName (In $days days)";
            $body = "Teacher $teacherName will be on leave from $dateStr. Reason: {$leave->reason}";
        }

        $customData = [
            'school_id' => $schoolId,
            'leave_id' => $leave->id
        ];

        // send_notification is a global helper
        send_notification($principalIds, $title, $body, 'Leave', $customData);
        $this->info("Sent $days-day reminder for $teacherName to " . count($principalIds) . " principals.");
    }
}
