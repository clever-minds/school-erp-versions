<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'test2@gmail.com')->first();
if ($user) {
    echo "User found. Roles:\n";
    foreach ($user->roles as $role) {
        echo "- " . $role->name . "\n";
    }
} else {
    echo "User not found\n";
}
