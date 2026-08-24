<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::table('students', function (Blueprint $table) {
            $table->enum('rte_status', ['RTE', 'NON_RTE'])
                ->default('NON_RTE')
                ->after('uni_no');  
                   });

    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('rte_status');
        });
    }
};
