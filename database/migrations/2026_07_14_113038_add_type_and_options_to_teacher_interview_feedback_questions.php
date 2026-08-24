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
        Schema::table('teacher_interview_feedback_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_interview_feedback_questions', 'type')) {
                $table->string('type')->nullable()->after('status');
            }
            if (!Schema::hasColumn('teacher_interview_feedback_questions', 'audit_option_group_id')) {
                $table->foreignId('audit_option_group_id')->nullable()->after('type')->constrained('audit_option_groups', 'id', 'tifq_audit_option_group_id_fk')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_interview_feedback_questions', function (Blueprint $table) {
            $table->dropForeign('tifq_audit_option_group_id_fk');
            $table->dropColumn(['type', 'audit_option_group_id']);
        });
    }
};
