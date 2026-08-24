<?php 
require 'vendor/autoload.php'; 
$app = require_once 'bootstrap/app.php'; 
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); 
$kernel->bootstrap(); 
$tables = Illuminate\Support\Facades\DB::select('SHOW TABLES');
echo json_encode($tables);
