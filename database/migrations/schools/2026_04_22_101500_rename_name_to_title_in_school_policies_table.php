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
        if (Schema::hasColumn('school_policies', 'name') && !Schema::hasColumn('school_policies', 'title')) {
            Schema::table('school_policies', function (Blueprint $table) {
                $table->renameColumn('name', 'title');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('school_policies', 'title') && !Schema::hasColumn('school_policies', 'name')) {
            Schema::table('school_policies', function (Blueprint $table) {
                $table->renameColumn('title', 'name');
            });
        }
    }
};
