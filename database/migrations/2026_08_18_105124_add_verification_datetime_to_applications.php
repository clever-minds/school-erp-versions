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
        Schema::table('teacher_interview_applications', function (Blueprint $table) {
            $table->date('document_verification_date')->nullable()->after('document_upload_token_expires_at');
            $table->time('document_verification_time')->nullable()->after('document_verification_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_interview_applications', function (Blueprint $table) {
            $table->dropColumn(['document_verification_date', 'document_verification_time']);
        });
    }
};
