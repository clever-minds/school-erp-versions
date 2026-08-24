<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected $commands = [Commands\SubscriptionBillCron::class,\App\Console\Commands\AddPermissionToAllTenants::class,\App\Console\Commands\MigrateAllTenants::class,\App\Console\Commands\RollbackAllTenants::class,    \App\Console\Commands\TenantsRollbackStudentsExtraFields::class,

];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('subscriptionBill:cron')->daily();
        $schedule->command('event:send-notifications')->dailyAt('01:00');
        $schedule->command('notifications:send-scheduled')->dailyAt('09:00');
        $schedule->command('leave:reminders')->dailyAt('07:00');
        $schedule->command('audit:archive')->dailyAt('02:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
