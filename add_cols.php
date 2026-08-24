<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

$tenants = DB::connection('mysql')->table('schools')->get();
foreach ($tenants as $tenant) {
    echo "Updating {$tenant->database_name}...\n";
    config(['database.connections.school.database' => $tenant->database_name]);
    DB::purge('school');
    DB::reconnect('school');
    
    if (Schema::connection('school')->hasTable('events') && !Schema::connection('school')->hasColumn('events', 'class_section_id')) {
        Schema::connection('school')->table('events', function(Blueprint $table) {
            $table->unsignedBigInteger('class_section_id')->nullable()->after('date');
        });
        echo " - Added class_section_id to events.\n";
    }
    
    if (Schema::connection('school')->hasTable('reminders') && !Schema::connection('school')->hasColumn('reminders', 'class_section_id')) {
        Schema::connection('school')->table('reminders', function(Blueprint $table) {
            $table->unsignedBigInteger('class_section_id')->nullable()->after('date');
        });
        echo " - Added class_section_id to reminders.\n";
    }
}
echo "Done.\n";
