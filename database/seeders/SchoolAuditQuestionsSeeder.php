<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SchoolAuditQuestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Option Groups
        $ratingOptions = [
            ['label' => 'Excellent'],
            ['label' => 'Good'],
            ['label' => 'Average'],
            ['label' => 'Unsatisfactory']
        ];
        
        $yesNoOptions = [
            ['label' => 'Yes'],
            ['label' => 'No']
        ];

        $yesNoConditionalRating = [
            ['label' => 'Yes', 'has_sub_options' => true, 'sub_options' => ['Excellent', 'Good', 'Average', 'Unsatisfactory']],
            ['label' => 'No', 'has_sub_options' => false]
        ];

        $teacherResponseOptions = [
            ['label' => 'Good'],
            ['label' => 'Bad']
        ];

        $activityLocationOptions = [
            ['label' => 'Inside'],
            ['label' => 'Outside']
        ];

        $likeMostOptions = [
            ['label' => 'Personality'],
            ['label' => 'Behaviour'],
            ['label' => 'Knowledge'],
            ['label' => 'Teaching']
        ];

        $groupRating = \App\Models\AuditOptionGroup::firstOrCreate(['name' => '4-Point Rating'], ['option_values' => $ratingOptions]);
        $groupYesNo = \App\Models\AuditOptionGroup::firstOrCreate(['name' => 'Yes/No'], ['option_values' => $yesNoOptions]);
        $groupYesNoConditional = \App\Models\AuditOptionGroup::firstOrCreate(['name' => 'Yes/No -> Rating'], ['option_values' => $yesNoConditionalRating]);
        $groupTeacherResponse = \App\Models\AuditOptionGroup::firstOrCreate(['name' => 'Good/Bad'], ['option_values' => $teacherResponseOptions]);
        $groupActivityLocation = \App\Models\AuditOptionGroup::firstOrCreate(['name' => 'Inside/Outside'], ['option_values' => $activityLocationOptions]);
        $groupLikeMost = \App\Models\AuditOptionGroup::firstOrCreate(['name' => 'Teacher Attributes'], ['option_values' => $likeMostOptions]);

        $data = [
            [
                'category' => 'Outside Environment of the School',
                'questions' => [
                    ['question' => 'Uniform of Guard', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Guard’s Behavior with students /Parents', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Notice Board/School Board', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Parking Area', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Sewerage', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Cleanliness in general', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Initial Findings',
                'questions' => [
                    ['question' => 'Was Principal available?', 'type' => 'boolean', 'audit_option_group_id' => $groupYesNo->id],
                    ['question' => 'Duty In-charge was available', 'type' => 'boolean', 'audit_option_group_id' => $groupYesNo->id],
                    ['question' => 'Total number of late students', 'type' => 'text', 'audit_option_group_id' => null],
                    ['question' => 'Total number of students not in proper dress code', 'type' => 'text', 'audit_option_group_id' => null],
                ]
            ],
            [
                'category' => 'Inside Environment of the School',
                'questions' => [
                    ['question' => 'Uniform of Menial Staff', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Staff Room', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Admin office', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Student Notice Board(Fully overrun)', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Staff Notice Board(Fully overrun)', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Library', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Computer Lab(if available)', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Biology Lab (if available)', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Chemistry Lab(if available)', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Physics Lab (if available)', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Play area(Fully overrun)', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Canteen', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Water Cooler', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'General cleanliness & condition of washrooms', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Admission Office',
                'questions' => [
                    ['question' => 'How is the ambience of the admission office?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'What impression does the admission office give?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'What is the general cleanliness condition of the admission office?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Does the admission office has soft board decorated according to the admission query?', 'type' => 'conditional', 'audit_option_group_id' => $groupYesNoConditional->id],
                    ['question' => 'Does the Principal/Admission office have soft board containing information regarding curricular and co curricular activities?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Is the admission officer well confident?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Do they have a proper sitting arrangement for visitors?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Does the admission office have proper data regarding all queries?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'What is the general condition of cleanliness of the admission office?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Administration Data Record',
                'questions' => [
                    ['question' => 'Record of teachers coming late', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Record of short leaves , Applications and Staff leaves', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Record of Students short leaves and leaves Applications', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Record of calls to absentees', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Record of staff meetings (Minute of the Meetings)', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Record of late comers (Students)', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => '(Principal )Morning Assembly',
                'questions' => [
                    ['question' => 'Is the morning assembly conducted regularly?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Are the assembly themes given in the manual being followed by the school Principal?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Are they given program format for the morning assembly?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Vice Principal Responsibilities',
                'questions' => [
                    ['question' => 'Monitoring of the Morning Assembly', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Monitoring of the Dress code', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Assigning the duties to Teachers & students', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Daily submission of duty report to the Principal', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Observation of the Bell Timing', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Class Rooms Observation',
                'questions' => [
                    ['question' => 'Fans, Lights & air condition', 'type' => 'boolean', 'audit_option_group_id' => $groupYesNo->id],
                    ['question' => 'Fulfillment of Montessori Req.', 'type' => 'boolean', 'audit_option_group_id' => $groupYesNo->id],
                    ['question' => 'Display of LCDs', 'type' => 'boolean', 'audit_option_group_id' => $groupYesNo->id],
                    ['question' => 'White Board', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Soft boards', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Chairs', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Tables', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Floor', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Paint & Display of Décor kit', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Class Room Management',
                'questions' => [
                    ['question' => 'How is the ambience of the decor kit?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Have the classrooms been kept neat and clean by the class teacher?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Have the soft boards of the classrooms arranged according to lesson plan', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Is the information given on the whiteboard according to the subject and timetable of the day?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Is the strength of student according to the given number of students? i.e:25-30 students each', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Are the classrooms monitoring registers (attendance register) being properly tagged and covered?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Are the classrooms theme based decorated?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Follow up of Academic Calendar',
                'questions' => [
                    ['question' => 'Are they following the academics activities according to the given consolidated calendar of school?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Are the activities being followed according to the given schedule of time?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Daily Lesson Plans (DLP’s)',
                'questions' => [
                    ['question' => 'Is the teacher teaching according to daily lesson plans?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Are the lesson plans followed according to the weekly breakup?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Are the lesson plans fulfilling the provided criteria for delivering the lecture?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Has teacher following the revisions week according to planner?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Is teacher following the given writing format?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Do teacher have the complete series of given Teachers Manual/Teachers Guide?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Copy Checking',
                'questions' => [
                    ['question' => 'Are the books and copies of the students wrapped neatly?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Has the teacher evaluated the class work copies in a correct date wise order?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Have the copies been super-checked by Coordinator/Principal?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Dairy Checking',
                'questions' => [
                    ['question' => 'Are the dairies properly wrapped and covered?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Does the homework given in diaries match with the homework instruction given in the Weekly planner?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'PTM',
                'questions' => [
                    ['question' => 'Are they conducting PTM according to the given consolidated calendar?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Have they been filling up their PTM Performa from their parents?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Specifications of Play Area',
                'questions' => [
                    ['question' => 'Is the play area of school well maintained?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Does the play area have some play stuff like see saw, swings & alike.', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Is the grass of play area is properly cropped?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'Does the play area is provided with any safety measure?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Water Cooler',
                'questions' => [
                    ['question' => 'Is the water clean?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                    ['question' => 'What is the condition of water glass?', 'type' => 'rating', 'audit_option_group_id' => $groupRating->id],
                ]
            ],
            [
                'category' => 'Student’s Feedback regarding teachers',
                'questions' => [
                    ['question' => 'Who is your favorite teacher & why?', 'type' => 'text', 'audit_option_group_id' => null],
                    ['question' => 'Which subject do you like most and why?', 'type' => 'text', 'audit_option_group_id' => null],
                    ['question' => 'Which subject you find boring?', 'type' => 'text', 'audit_option_group_id' => null],
                    ['question' => 'Are you satisfied about the methodology of teaching?', 'type' => 'boolean', 'audit_option_group_id' => $groupYesNo->id],
                    ['question' => 'Which thing you like most about your teacher?', 'type' => 'rating', 'audit_option_group_id' => $groupLikeMost->id],
                    ['question' => 'Is the teacher covering the syllabus in time?', 'type' => 'boolean', 'audit_option_group_id' => $groupYesNo->id],
                    ['question' => 'What is the response of teacher when you ask the question about your topic?', 'type' => 'rating', 'audit_option_group_id' => $groupTeacherResponse->id],
                    ['question' => 'Do you think your teacher’s teaching methodology is activity based?', 'type' => 'boolean', 'audit_option_group_id' => $groupYesNo->id],
                    ['question' => 'Where do you like to perform your activities?', 'type' => 'rating', 'audit_option_group_id' => $groupActivityLocation->id],
                    ['question' => 'Do you find friendly environment in your class?', 'type' => 'boolean', 'audit_option_group_id' => $groupYesNo->id],
                    ['question' => 'Give some comments about the teacher.', 'type' => 'text', 'audit_option_group_id' => null],
                ]
            ],
        ];

        foreach ($data as $section) {
            foreach ($section['questions'] as $q) {
                \App\Models\AuditQuestion::updateOrCreate(
                    [
                        'question' => $q['question'],
                        'category' => $section['category'],
                    ],
                    [
                        'type' => $q['type'],
                        'audit_option_group_id' => $q['audit_option_group_id'],
                        'status' => 1
                    ]
                );
            }
        }
    }
}
