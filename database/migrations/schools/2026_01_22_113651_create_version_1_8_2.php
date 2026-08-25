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
        // Step 1: Add new columns
        try {
            Schema::table('online_exam_student_answers', function (Blueprint $table) {
                $table->tinyInteger('true_answer')->nullable()->after('option_id');
                $table->integer('question_marks')->nullable()->after('true_answer');
            });
        } catch (\Exception $e) {
        }
        // Step 2: Drop foreign keys temporarily

        try {
            Schema::table('online_exam_student_answers', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
                $table->dropForeign(['student_id']);
                $table->dropForeign(['online_exam_id']);
                $table->dropForeign(['question_id']);
                $table->dropForeign(['option_id']);
            });
        } catch (\Exception $e) {
        }

        try {

            // Step 3: Add temporary indexes for faster updates
            Schema::table('online_exam_student_answers', function (Blueprint $table) {
                $table->index('option_id', 'temp_option_idx');
                $table->index('question_id', 'temp_question_idx');
            });

            // Step 4: Fill the data in chunks
            $this->fillData();

            // Step 5: Remove temporary indexes
            Schema::table('online_exam_student_answers', function (Blueprint $table) {
                $table->dropIndex('temp_option_idx');
                $table->dropIndex('temp_question_idx');
            });

            // Step 6: Add foreign keys back
            Schema::table('online_exam_student_answers', function (Blueprint $table) {
                $table->foreign('school_id')->references('id')->on('schools')->onDelete('no action');
                $table->foreign('student_id')->references('id')->on('users')->onDelete('no action');
                $table->foreign('online_exam_id')->references('id')->on('online_exams')->onDelete('no action');
                $table->foreign('question_id')->references('id')->on('online_exam_question_choices')->onDelete('no action');
                $table->foreign('option_id')->references('id')->on('online_exam_question_options')->onDelete('no action');
            });

            Schema::create('fcm_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('fcm_token', 767);
                $table->enum('device_type', ['android', 'ios', 'web'])->default('android');
                $table->string('device_id', 255)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique('fcm_token');
                $table->index(['user_id', 'device_type']);
                $table->index('last_used_at');
            });
        } catch (\Exception $e) {
        }
    }

    private function fillData(): void
    {
        $chunkSize = 5000;
        $minId = DB::table('online_exam_student_answers')->min('id');
        $maxId = DB::table('online_exam_student_answers')->max('id');

        if (!$minId || !$maxId) {
            return; // No data to process
        }

        for ($start = $minId; $start <= $maxId; $start += $chunkSize) {
            $end = $start + $chunkSize - 1;

            // Update true_answer column
            DB::statement("
                UPDATE online_exam_student_answers osa
                JOIN online_exam_question_options opts ON opts.id = osa.option_id
                SET osa.true_answer = IF(opts.is_answer = 1, 1, 0)
                WHERE osa.id BETWEEN ? AND ?
                  AND osa.option_id IS NOT NULL
            ", [$start, $end]);

            // Update question_marks column
            DB::statement("
                UPDATE online_exam_student_answers osa
                JOIN online_exam_question_choices qc ON qc.id = osa.question_id
                SET osa.question_marks = qc.marks
                WHERE osa.id BETWEEN ? AND ?
                  AND osa.question_id IS NOT NULL
            ", [$start, $end]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop foreign keys
        Schema::table('online_exam_student_answers', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropForeign(['student_id']);
            $table->dropForeign(['online_exam_id']);
            $table->dropForeign(['question_id']);
            $table->dropForeign(['option_id']);
        });

        // Step 2: Drop the new columns
        Schema::table('online_exam_student_answers', function (Blueprint $table) {
            $table->dropColumn(['true_answer', 'question_marks']);
        });

        // Step 3: Add foreign keys back
        Schema::table('online_exam_student_answers', function (Blueprint $table) {
            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('student_id')->references('id')->on('users');
            $table->foreign('online_exam_id')->references('id')->on('online_exams');
            $table->foreign('question_id')->references('id')->on('online_exam_question_choices');
            $table->foreign('option_id')->references('id')->on('online_exam_question_options');
        });

        Schema::dropIfExists('fcm_tokens');
    }
};
