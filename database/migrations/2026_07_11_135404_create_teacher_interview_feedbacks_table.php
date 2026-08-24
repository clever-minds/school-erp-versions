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
        Schema::create('teacher_interview_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained('teacher_interviews')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('teacher_interview_feedback_questions')->cascadeOnDelete();
            $table->text('interviewer_feedback')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_interview_feedbacks');
    }
};
