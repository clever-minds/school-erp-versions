<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$student = \App\Models\Student::where('admission_no', 'LTA00065')->first();
if ($student) {
    echo "Student ID: " . $student->id . "\n";
    echo "User ID: " . $student->user_id . "\n";

    $compulsoryFee = \App\Models\CompulsoryFee::where('student_id', $student->user_id)->first();
    echo "Compulsory fee with student_id = user_id: " . ($compulsoryFee ? "Found" : "Not Found") . "\n";

    $compulsoryFee2 = \App\Models\CompulsoryFee::where('student_id', $student->id)->first();
    echo "Compulsory fee with student_id = student_id: " . ($compulsoryFee2 ? "Found" : "Not Found") . "\n";
} else {
    echo "Student not found\n";
}
