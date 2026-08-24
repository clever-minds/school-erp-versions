<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('school_audits', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0)->comment('0: Pending, 1: Completed')->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_audits', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
