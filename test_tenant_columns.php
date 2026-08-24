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
    $columns = \Illuminate\Support\Facades\DB::connection('tenant')->getSchemaBuilder()->getColumnListing('staff_attendances');
    echo json_encode($columns);
}
