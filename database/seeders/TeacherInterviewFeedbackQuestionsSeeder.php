<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeacherInterviewFeedbackQuestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Option Groups
        $assessmentOptions = [
            ['label' => 'Below Average'],
            ['label' => 'Satisfactory'],
            ['label' => 'V.Good'],
            ['label' => 'Excellent']
        ];
        
        $attitudeOptions = [
            ['label' => 'Logical'],
            ['label' => 'Emotional'],
            ['label' => 'Balanced'],
            ['label' => 'Cooperative'],
            ['label' => 'Non-Cooperative']
        ];

        $overallOptions = [
            ['label' => 'Not selected.'],
            ['label' => 'May be selected if there is not better choice.'],
            ['label' => 'Good candidate'],
            ['label' => 'Best candidate/ should be appointed at any cost.'],
            ['label' => 'Good candidate but too expensive.']
        ];

        $assessmentGroup = \App\Models\AuditOptionGroup::firstOrCreate(
            ['name' => 'Teacher Interview: 4-Point Assessment'],
            ['option_values' => $assessmentOptions]
        );

        $attitudeGroup = \App\Models\AuditOptionGroup::firstOrCreate(
            ['name' => 'Teacher Interview: Attitude Assessment'],
            ['option_values' => $attitudeOptions]
        );

        $overallGroup = \App\Models\AuditOptionGroup::firstOrCreate(
            ['name' => 'Teacher Interview: Overall Rating'],
            ['option_values' => $overallOptions]
        );

        // 2. Clear existing questions for a fresh start (optional, but requested by logic to have this exact process)
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \App\Models\TeacherInterviewFeedbackQuestion::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // 3. Insert Questions
        $questions = [
            // Personality
            ['category' => 'Personality', 'feedback_question' => 'General appearance', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Personality', 'feedback_question' => 'Confidence', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Personality', 'feedback_question' => 'Intelligence', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Personality', 'feedback_question' => 'Manners', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Personality', 'feedback_question' => 'Expressions', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Personality', 'feedback_question' => 'Gestures', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Personality', 'feedback_question' => 'Devotion', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Personality', 'feedback_question' => 'Flexibility in opinion', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Personality', 'feedback_question' => 'Softness in nature', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Personality', 'feedback_question' => 'Strictness in nature', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],

            // Communication
            ['category' => 'Communication', 'feedback_question' => 'Speaking (Urdu)', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Communication', 'feedback_question' => 'Speaking (English)', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Communication', 'feedback_question' => 'Listening', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Communication', 'feedback_question' => 'Writing', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],

            // Educational Qualification
            ['category' => 'Educational Qualification', 'feedback_question' => 'General knowledge', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Educational Qualification', 'feedback_question' => 'Professional Knowledge', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Educational Qualification', 'feedback_question' => 'Knowledge of chosen subjects', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Educational Qualification', 'feedback_question' => 'Religious knowledge', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Educational Qualification', 'feedback_question' => 'Achievements in previous job', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Educational Qualification', 'feedback_question' => 'Preference the job interview for', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],

            // Co-Curricular Activities
            ['category' => 'Co-Curricular Activities', 'feedback_question' => 'Quran/Tajweed', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Co-Curricular Activities', 'feedback_question' => 'Speech/debate', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Co-Curricular Activities', 'feedback_question' => 'Performing art', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Co-Curricular Activities', 'feedback_question' => 'Sports/P.T', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],
            ['category' => 'Co-Curricular Activities', 'feedback_question' => 'Literature /poetry', 'type' => 'rating', 'audit_option_group_id' => $assessmentGroup->id],

            // Attitude
            ['category' => 'Attitude', 'feedback_question' => 'Attitude', 'type' => 'rating', 'audit_option_group_id' => $attitudeGroup->id],

            // Others
            ['category' => 'Other', 'feedback_question' => 'Referred by', 'type' => 'text', 'audit_option_group_id' => null],
            ['category' => 'Other', 'feedback_question' => 'Overall rating', 'type' => 'rating', 'audit_option_group_id' => $overallGroup->id],
            ['category' => 'Other', 'feedback_question' => 'Remarks', 'type' => 'text', 'audit_option_group_id' => null],
            ['category' => 'Other', 'feedback_question' => 'Recommended for appointment at a ground salary of', 'type' => 'text', 'audit_option_group_id' => null],
        ];

        foreach ($questions as $q) {
            \App\Models\TeacherInterviewFeedbackQuestion::create(array_merge($q, ['status' => 'active']));
        }
    }
}
