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
        if (Schema::hasColumn('teacher_documents', 'name') && !Schema::hasColumn('teacher_documents', 'type')) {
            Schema::table('teacher_documents', function (Blueprint $table) {
                $table->renameColumn('name', 'type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('teacher_documents', 'type') && !Schema::hasColumn('teacher_documents', 'name')) {
            Schema::table('teacher_documents', function (Blueprint $table) {
                $table->renameColumn('type', 'name');
            });
        }
    }
};
