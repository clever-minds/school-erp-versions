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
            $table->dropColumn('category');
            $table->unsignedBigInteger('teacher_interview_category_id')->nullable()->after('feedback_question');
            $table->foreign('teacher_interview_category_id', 'tifq_category_foreign')->references('id')->on('teacher_interview_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_interview_feedback_questions', function (Blueprint $table) {
            $table->dropForeign('tifq_category_foreign');
            $table->dropColumn('teacher_interview_category_id');
            $table->string('category')->nullable()->after('feedback_question');
        });
    }
};
