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
        Schema::create('teacher_offer_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('teacher_interview_applications')->onDelete('cascade');
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->date('joining_date')->nullable();
            $table->time('reporting_time')->nullable();
            $table->string('job_location')->nullable();
            $table->string('token')->unique()->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->enum('status', ['Prepared', 'Sent', 'Accepted', 'Rejected', 'Joined'])->default('Prepared');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_offer_letters');
    }
};
