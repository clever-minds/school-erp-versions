<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AuditCategory;
use App\Models\TeacherInterviewCategory;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $auditCategories = [
            ['name' => 'Academic', 'description' => 'Academic performance and standards', 'status' => 1],
            ['name' => 'Infrastructure', 'description' => 'School buildings and facilities', 'status' => 1],
            ['name' => 'Cleanliness', 'description' => 'Hygiene and cleanliness standards', 'status' => 1],
            ['name' => 'Security', 'description' => 'Safety and security measures', 'status' => 1],
            ['name' => 'Administration', 'description' => 'Administrative operations', 'status' => 1],
        ];

        foreach ($auditCategories as $cat) {
            AuditCategory::updateOrCreate(['name' => $cat['name']], $cat);
        }

        $interviewCategories = [
            ['name' => 'Technical Skills', 'description' => 'Subject matter expertise', 'status' => 1],
            ['name' => 'Communication', 'description' => 'Verbal and non-verbal communication', 'status' => 1],
            ['name' => 'Pedagogy', 'description' => 'Teaching methodology and approach', 'status' => 1],
            ['name' => 'Classroom Management', 'description' => 'Ability to manage students', 'status' => 1],
            ['name' => 'General / Behavioral', 'description' => 'General behavior and attitude', 'status' => 1],
        ];

        foreach ($interviewCategories as $cat) {
            TeacherInterviewCategory::updateOrCreate(['name' => $cat['name']], $cat);
        }
    }
}
