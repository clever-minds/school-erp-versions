<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\BulkNotificationsJobv3_0_0;
use App\Models\ClassSubject;
use App\Models\Lesson;
use App\Models\Students;
use App\Models\Subject;
use Illuminate\Support\Facades\Log;

final class LessonNotificationService
{
    public function __construct(
        private readonly CachingService $cache
    ) {}

    /**
     * Send optimized push notifications to relevant students and guardians.
     *
     * @param Lesson $lesson
     * @param array $sectionIds
     * @param int $subjectId
     * @param string $action 'created' or 'updated'
     */
    public function send(Lesson $lesson, array $sectionIds, int $subjectId, string $action = 'created'): void
    {
        try {
            $sessionYear = $this->cache->getSessionYear();
            if (!$sessionYear) {
                return;
            }

            // 1. Resolve ClassSubjects for these sections and subject
            $classSubjects = ClassSubject::whereIn('class_id', function ($query) use ($sectionIds) {
                $query->select('class_id')
                    ->from('class_sections')
                    ->whereIn('id', $sectionIds);
            })
                ->where('subject_id', $subjectId)
                ->where('session_year_id', $sessionYear->id)
                ->get();

            if ($classSubjects->isEmpty()) {
                return;
            }

            // 2. Map sections to their ClassSubject models
            // Note: Different sections might belong to different classes, hence different class_subject records.
            $sectionToClassSubject = [];
            $compulsorySectionIds = [];
            $electivePairs = [];

            // We need to know which class each section belongs to
            $sections = \App\Models\ClassSection::whereIn('id', $sectionIds)->get(['id', 'class_id']);
            $classToSubject = $classSubjects->keyBy('class_id');

            foreach ($sections as $section) {
                $cs = $classToSubject->get($section->class_id);
                if ($cs) {
                    $sectionToClassSubject[$section->id] = $cs;
                    if ($cs->type === 'Compulsory') {
                        $compulsorySectionIds[] = $section->id;
                    } else {
                        $electivePairs[] = [
                            'class_section_id' => $section->id,
                            'class_subject_id' => $cs->id
                        ];
                    }
                }
            }

            // 3. Build optimized student query
            $studentsQuery = Students::query()
                ->whereIn('class_section_id', $sectionIds)
                ->where('session_year_id', $sessionYear->id);

            $studentsQuery->where(function ($q) use ($compulsorySectionIds, $electivePairs) {
                $hasCondition = false;

                if (!empty($compulsorySectionIds)) {
                    $q->whereIn('class_section_id', $compulsorySectionIds);
                    $hasCondition = true;
                }

                if (!empty($electivePairs)) {
                    $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                    $q->$method('user_id', function ($sub) use ($electivePairs) {
                        $sub->select('student_id')
                            ->from('student_subjects')
                            ->where(function ($inner) use ($electivePairs) {
                                foreach ($electivePairs as $pair) {
                                    $inner->orWhere(function ($c) use ($pair) {
                                        $c->where('class_section_id', $pair['class_section_id'])
                                            ->where('class_subject_id', $pair['class_subject_id']);
                                    });
                                }
                            });
                    });
                }
            });

            $students = $studentsQuery->get(['id', 'user_id', 'guardian_id', 'class_section_id']);

            if ($students->isEmpty()) {
                return;
            }

            // 4. Prepare data for BulkNotificationsJob
            $subject = Subject::find($subjectId);
            $subjectName = $subject->name ?? 'Subject';
            $subjectType = $subject->type ?? '';

            $title = 'Lesson Alert !!!';
            $body = "New study material $action for lesson " . $lesson->name . " in subject " . $subjectName . ($subjectType ? " - $subjectType" : "");

            $userIds = [];
            $studentMap = []; // user_id => student_id
            $guardianMap = []; // user_id => [student_ids]
            $sectionMap = []; // section_id => class_subject_id

            foreach ($sectionToClassSubject as $sectionId => $cs) {
                $sectionMap[$sectionId] = $cs->id;
            }

            foreach ($students as $student) {
                if ($student->user_id) {
                    $userIds[] = $student->user_id;
                    $studentMap[$student->user_id] = $student->id;
                }

                if ($student->guardian_id) {
                    $userIds[] = $student->guardian_id;
                    if (!isset($guardianMap[$student->guardian_id])) {
                        $guardianMap[$student->guardian_id] = [];
                    }
                    $guardianMap[$student->guardian_id][] = $student->id;
                }
            }

            $userIds = array_unique($userIds);

            $customData = [
                'internal' => [
                    'student_map' => $studentMap,
                    'guardian_map' => $guardianMap,
                    'section_map' => $sectionMap,
                ],
                'payload' => [
                    'lesson_id' => $lesson->id,
                    'subject_id' => $subjectId,
                ]
            ];

            // 5. Dispatch Job
            BulkNotificationsJobv3_0_0::dispatch(
                (int) $lesson->school_id, // Lesson should have school_id or we get it from Auth
                $userIds,
                $title,
                $body,
                'lesson',
                $customData
            );
        } catch (\Throwable $e) {
            Log::error('LessonNotificationService Error: ' . $e->getMessage(), [
                'lesson_id' => $lesson->id,
                'stack' => $e->getTraceAsString()
            ]);
        }
    }
}
