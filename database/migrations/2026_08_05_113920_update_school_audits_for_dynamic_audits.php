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
            if (!Schema::hasColumn('school_audits', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('school_audits', 'frequency')) {
                $table->string('frequency')->nullable();
            }
            if (!Schema::hasColumn('school_audits', 'due_date')) {
                $table->date('due_date')->nullable()->after('audit_date');
            }
            if (!Schema::hasColumn('school_audits', 'submission_date')) {
                $table->dateTime('submission_date')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('school_audits', 'percentage_score')) {
                $table->decimal('percentage_score', 5, 2)->nullable()->after('submission_date');
            }
            if (!Schema::hasColumn('school_audits', 'archived_at')) {
                $table->timestamp('archived_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_audits', function (Blueprint $table) {
            $table->dropColumn(['name', 'frequency', 'due_date', 'submission_date', 'percentage_score', 'archived_at']);
        });
    }
};
