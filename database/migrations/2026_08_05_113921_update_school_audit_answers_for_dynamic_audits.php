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
        Schema::table('school_audit_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('school_audit_answers', 'image')) {
                $table->string('image')->nullable();
            }
            // Increase answer size if needed for large text blocks, assuming it's already text based on earlier view but let's be safe.
            // In 2026_06_29_135517_create_school_audit_answers_table.php, answer is 'text'. So it's fine.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_audit_answers', function (Blueprint $table) {
            if (Schema::hasColumn('school_audit_answers', 'image')) {
                $table->dropColumn('image');
            }
        });
    }
};
