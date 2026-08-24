<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$qs = \App\Models\TeacherInterviewFeedbackQuestion::all(['id', 'feedback_question', 'type']);
foreach($qs as $q) {
    echo $q->id . ' | ' . $q->feedback_question . ' | Type: ' . ($q->type ?? 'NULL') . "\n";
}
