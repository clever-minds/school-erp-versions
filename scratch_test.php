<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = Illuminate\Support\Facades\DB::connection('school')->select('DESCRIBE fees_paids');
print_r($cols);

$cols2 = Illuminate\Support\Facades\DB::connection('school')->select('DESCRIBE compulsory_fees');
print_r($cols2);
