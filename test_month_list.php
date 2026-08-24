<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$school_id = 10; // From user's screenshot
$date = Carbon\Carbon::create(null, 7, 1);
$staffUsers = App\Models\User::where('school_id', $school_id)
            ->whereHas('roles', function($q) {
                $q->whereNotIn('name', ['Student', 'Parent']);
            })->orderBy('first_name', 'ASC')->get();

$total = $staffUsers->count();
$rows = array();
$staffAttendanceModel = new App\Models\StaffAttendance(); // Or however it is injected
foreach ($staffUsers as $staffUser) {            
    $staffAttendance = ['full_name' => $staffUser->full_name, 'user_id' => $staffUser->id];
    
    for ($day=1; $day <= $date->daysInMonth; $day++) {
        $currentDate = $date->copy()->day($day)->format('Y-m-d');
        // We use DB query directly to bypass tenant scopes if they interfere
        $attendance = DB::table('staff_attendances')->where('user_id', $staffUser->id)->where('date', $currentDate)->first();
        if ($attendance) {
            if (isset($attendance->type) && $attendance->type === 'Work From Home') {
                $staffAttendance["day_$day"] = 'WFH';
            } elseif ($attendance->status == 1) {
                $staffAttendance["day_$day"] = 'P';
            } else {
                $staffAttendance["day_$day"] = 'A';
            }
        } else {
            $staffAttendance["day_$day"] = null;
        }
    }
    $rows[] = $staffAttendance;
}

$bulkData = array();
$bulkData['total'] = $total;
$bulkData['rows'] = $rows;

echo json_encode($bulkData);
