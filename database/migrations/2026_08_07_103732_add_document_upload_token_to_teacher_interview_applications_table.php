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
            $table->string('document_upload_token')->nullable()->after('status');
            $table->timestamp('document_upload_token_expires_at')->nullable()->after('document_upload_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_interview_applications', function (Blueprint $table) {
            //
        });
    }
};
