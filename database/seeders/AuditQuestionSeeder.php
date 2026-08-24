<?php

namespace Database\Seeders;

use App\Models\AuditQuestion;
use Illuminate\Database\Seeder;

class AuditQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                "category" => "Outside Environment of the School",
                "questions" => [
                    "Uniform of Guard",
                    "Guard’s Behavior with students /Parents",
                    "Notice Board/School Board",
                    "Parking Area",
                    "Sewerage",
                    "Cleanliness in general"
                ]
            ],
            [
                "category" => "Initial Findings",
                "questions" => [
                    "Was Principal available?",
                    "Duty In-charge was available",
                    "Total number of late students",
                    "Total number of students not in proper dress code"
                ]
            ],
            [
                "category" => "Inside Environment of the School",
                "questions" => [
                    "Uniform of Menial Staff",
                    "Staff Room",
                    "Admin office",
                    "Student Notice Board(Fully overrun)",
                    "Staff Notice Board(Fully overrun)",
                    "Library",
                    "Computer Lab(if available)",
                    "Biology Lab (if available)",
                    "Chemistry Lab(if available)",
                    "Physics Lab (if available)",
                    "Play area(Fully overrun)",
                    "Canteen",
                    "Water Cooler",
                    "General cleanliness & condition of washrooms"
                ]
            ],
            [
                "category" => "Admission Office",
                "questions" => [
                    "How is the ambience of the admission office?",
                    "What impression does the admission office give?",
                    "What is the general cleanliness condition of the admission office?",
                    "Does the admission office has soft board decorated according to the admission query?",
                    "Does the Principal/Admission office have soft board containing information regarding curricular and co curricular activities?",
                    "Is the admission officer well confident?",
                    "Do they have a proper sitting arrangement for visitors?",
                    "Does the admission office have proper data regarding all queries?",
                    "What is the general condition of cleanliness of the admission office?"
                ]
            ],
            [
                "category" => "Administration Data Record",
                "questions" => [
                    "Record of teachers coming late",
                    "Record of short leaves , Applications and Staff leaves",
                    "Record of Students short leaves and leaves Applications",
                    "Record of calls to absentees",
                    "Record of staff meetings (Minute of the Meetings)",
                    "Record of late comers (Students)"
                ]
            ],
            [
                "category" => "(Principal )Morning Assembly",
                "questions" => [
                    "Is the morning assembly conducted regularly?",
                    "Are the assembly themes given in the manual being followed by the school Principal?",
                    "Are they given program format for the morning assembly?"
                ]
            ],
            [
                "category" => "Vice Principal Responsibilities",
                "questions" => [
                    "Monitoring of the Morning Assembly",
                    "Monitoring of the Dress code",
                    "Assigning the duties to Teachers & students",
                    "Daily submission of duty report to the Principal",
                    "Observation of the Bell Timing"
                ]
            ],
            [
                "category" => "Class Rooms Observation",
                "questions" => [
                    "Fans, Lights & air condition",
                    "Fulfillment of Montessori Req.",
                    "Display of LCDs",
                    "White Board",
                    "Soft boards",
                    "Chairs",
                    "Tables",
                    "Floor",
                    "Paint & Display of Décor kit"
                ]
            ],
            [
                "category" => "Class Room Management",
                "questions" => [
                    "How is the ambience of the decor kit?",
                    "Have the classrooms been kept neat and clean by the class teacher?",
                    "Have the soft boards of the classrooms arranged according to lesson plan",
                    "Is the information given on the whiteboard according to the subject and timetable of the day?",
                    "Is the strength of student according to the given number of students? i.e:25-30 students each",
                    "Are the classrooms monitoring registers (attendance register) being properly tagged and covered?",
                    "Are the classrooms theme based decorated?"
                ]
            ],
            [
                "category" => "Follow up of Academic Calendar",
                "questions" => [
                    "Are they following the academics activities according to the given consolidated calendar of school?",
                    "Are the activities being followed according to the given schedule of time?"
                ]
            ],
            [
                "category" => "Daily Lesson Plans (DLP’s)",
                "questions" => [
                    "Is the teacher teaching according to daily lesson plans?",
                    "Are the lesson plans followed according to the weekly breakup?",
                    "Are the lesson plans fulfilling the provided criteria for delivering the lecture?",
                    "Has teacher following the revisions week according to planner?",
                    "Is teacher following the given writing format?",
                    "Do teacher have the complete series of given Teachers Manual/Teachers Guide?"
                ]
            ],
            [
                "category" => "Copy Checking",
                "questions" => [
                    "Are the books and copies of the students wrapped neatly?",
                    "Has the teacher evaluated the class work copies in a correct date wise order?",
                    "Have the copies been super-checked by Coordinator/Principal?"
                ]
            ],
            [
                "category" => "Dairy Checking",
                "questions" => [
                    "Are the dairies properly wrapped and covered?",
                    "Does the homework given in diaries match with the homework instruction given in the Weekly planner?"
                ]
            ],
            [
                "category" => "PTM",
                "questions" => [
                    "Are they conducting PTM according to the given consolidated calendar?",
                    "Have they been filling up their PTM Performa from their parents?"
                ]
            ],
            [
                "category" => "Specifications of Play Area",
                "questions" => [
                    "Is the play area of school well maintained?",
                    "Does the play area have some play stuff like see saw, swings & alike.",
                    "Is the grass of play area is properly cropped?",
                    "Does the play area is provided with any safety measure?"
                ]
            ],
            [
                "category" => "Water Cooler",
                "questions" => [
                    "Is the water clean?",
                    "What is the condition of water glass?"
                ]
            ],
            [
                "category" => "Student’s Feedback regarding teachers during Class Room Visit",
                "questions" => [
                    "Who is your favorite teacher & why?",
                    "Which subject do you like most and why?",
                    "Which subject you find boring?"
                ]
            ],
            [
                "category" => "Teacher Effectiveness (Student Feedback)",
                "questions" => [
                    "Are you satisfied about the methodology of teaching?",
                    "Which thing you like most about your teacher?",
                    "Is the teacher covering the syllabus in time?",
                    "What is the response of teacher when you ask the question about your topic?",
                    "Do you think your teacher’s teaching methodology is activity based?",
                    "Where do you like to perform your activities?",
                    "Do you find friendly environment in your class?"
                ]
            ]
        ];

        foreach ($categories as $cat) {
            foreach ($cat['questions'] as $q) {
                AuditQuestion::firstOrCreate([
                    'question' => $q,
                    'category' => $cat['category']
                ], [
                    'status' => 1
                ]);
            }
        }
    }
}
