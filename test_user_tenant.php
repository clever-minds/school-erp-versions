<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Explicitly set the tenant connection
\Illuminate\Support\Facades\Config::set('database.default', 'tenant');
$user = App\Models\User::on('tenant')->find(726); 
if($user) {
    print_r("User 726 found! Roles: ");
    print_r($user->roles->pluck('name')->toArray());
} else {
    print_r("User 726 not found on tenant DB.");
}
