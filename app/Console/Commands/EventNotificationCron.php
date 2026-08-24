<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;
use App\Models\NotificationUser;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class EventNotificationCron extends Command
{
    protected $signature = 'event:send-notifications';
    protected $description = 'Send Event Reminder notifications (school wise)';

    public function handle()
    {
        $today = Carbon::today();

        $cases = [
            '7_days'    => $today->copy()->addDays(7)->toDateString(),
            '1_day'     => $today->copy()->addDay()->toDateString(),
            'event_day' => $today->toDateString(),
        ];

        /* ===============================
         | MASTER DB
         |===============================*/
        DB::setDefaultConnection('mysql');

        School::where('status', 1)->chunk(20, function ($schools) use ($cases) {

            foreach ($schools as $school) {

                if (empty($school->database_name)) {
                    continue;
                }

                /* ===============================
                 | SWITCH TO SCHOOL DB
                 |===============================*/
                Config::set(
                    'database.connections.school.database',
                    $school->database_name
                );

                DB::purge('school');
                DB::connection('school')->reconnect();
$customData=[];
                foreach ($cases as $type => $date) {

                    // ✅ FORCE SCHOOL CONNECTION
                    $notifications = (new Notification)
                        ->setConnection('school')
                        ->whereDate('event_date', $date)
                        ->get();

                    foreach ($notifications as $notification) {

                        /* ===============================
                         | CUSTOM DATA (SAFE)
                         |===============================*/
                        $customData = [
                            'event_date' => $notification->event_date
                                ? Carbon::parse($notification->event_date)->toDateString()
                                : null,
                            'image' => $notification->image ?? null,
                            'school_id'=>$school->id
                        ];

                        $users = (new NotificationUser)
                            ->setConnection('school')
                            ->where('notification_id', $notification->id)
                            ->select('user_id')
                            ->distinct()
                            ->get();
   Log::info('Custom Data', $customData);

                        foreach ($users as $row) {

                            send_notification_all(
                                $notification,
                                $row->user_id,
                                $type,
                                $customData
                            );
                        }
                    }
                }
            }
        });

        $this->info('✅ Multi-tenant event notifications sent successfully');
    }
}
