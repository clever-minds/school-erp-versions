<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\School;
$school = School::find(10);
if ($school) {
    \Illuminate\Support\Facades\Config::set('database.connections.tenant.database', $school->database_name);
    \Illuminate\Support\Facades\DB::purge('tenant');
    \Illuminate\Support\Facades\DB::reconnect('tenant');
    
    // Fake request for monthWiseList simulation
    $staffUser = App\Models\User::on('tenant')->where('school_id', 10)->first();
    if($staffUser) {
        $attendance = App\Models\StaffAttendance::on('tenant')->updateOrCreate(
            ['user_id' => $staffUser->id, 'date' => '2026-08-01'],
            ['status' => 1, 'type' => 'Work From Home', 'school_id' => 10]
        );
        echo "Saved: " . json_encode($attendance->toArray()) . "\n";
        
        $fetched = App\Models\StaffAttendance::on('tenant')->where('user_id', $staffUser->id)->where('date', '2026-08-01')->first();
        echo "Fetched: " . json_encode($fetched->toArray()) . "\n";
    }
}
