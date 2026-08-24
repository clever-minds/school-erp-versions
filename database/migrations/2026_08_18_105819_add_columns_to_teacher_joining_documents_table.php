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
        Schema::table('teacher_joining_documents', function (Blueprint $table) {
            $table->foreignId('application_id')->nullable()->constrained('teacher_interview_applications')->onDelete('cascade');
            $table->string('document_type')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('Pending');
            $table->text('remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_joining_documents', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
            $table->dropColumn(['application_id', 'document_type', 'file_path', 'status', 'remarks']);
        });
    }
};
