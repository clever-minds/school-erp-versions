<?php

use App\Models\LessonTopic;
use App\Models\LessonTopicClass;
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
        Schema::dropIfExists('lesson_topic_classes');
        Schema::create('lesson_topic_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_topic_id')->constrained('lesson_topics')->onDelete('cascade');
            $table->foreignId('class_section_id')->constrained('class_sections')->onDelete('cascade');
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->unique(['lesson_topic_id', 'class_section_id'], 'lesson_topic_class_unique');
            $table->timestamps();
        });


        $lessonTopics = LessonTopic::with('lesson.lesson_commons')->get();

        $lessonTopicClassData = [];
        foreach ($lessonTopics ?? [] as $key => $topic) {
            foreach ($topic->lesson->lesson_commons ?? [] as $key => $lessonCommon) {
                $lessonTopicClassData[] = [
                    'lesson_topic_id' => $topic->id,
                    'class_section_id' => $lessonCommon->class_section_id,
                    'school_id' => $topic->school_id
                ];
            }
        }

        LessonTopicClass::upsert($lessonTopicClassData, ['lesson_topic_id', 'class_section_id'], ['school_id']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_topic_classes');
    }
};
