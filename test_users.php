<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$school_id = 10;
$users = App\Models\User::where('school_id', $school_id)
            ->whereHas('roles', function($q) {
                $q->whereNotIn('name', ['Student', 'Parent']);
            })->count();
print_r("Count: " . $users);
