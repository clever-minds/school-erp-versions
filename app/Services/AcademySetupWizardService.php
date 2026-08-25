<?php

namespace App\Services;

use App\Models\SchClass;
use App\Models\SchMedium;
use App\Models\SchSection;
use App\Models\SchStream;
use App\Models\SchSubject;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\SessionYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AcademySetupWizardService
{
    public function defaultProgress(): array
    {
        return [
            'status' => 'pending',
            'message' => 'Waiting to start setup',
            'checkpoints' => [
                'session_year' => ['label' => 'Academic Year created', 'status' => 'pending'],
                'mediums_sections' => ['label' => 'Mediums & Sections created', 'status' => 'pending'],
                'shifts' => ['label' => 'Shifts configured', 'status' => 'pending'],
                'streams' => ['label' => 'Streams created', 'status' => 'pending'],
                'classes_subjects' => ['label' => 'Classes & Subjects mapped', 'status' => 'pending'],
                'syllabus' => ['label' => 'Syllabus generated and linked', 'status' => 'pending'],
                'completed' => ['label' => 'Setup completed', 'status' => 'pending'],
            ],
        ];
    }

    public function initializeRun(int $schoolId, string $runId): array
    {
        $progress = $this->defaultProgress();
        $progress['status'] = 'processing';
        $progress['message'] = 'Setup started';

        $this->setSchoolSetting($schoolId, 'academy_setup_run_id', $runId);
        $this->setSchoolSetting($schoolId, 'academy_setup_in_progress', '1');
        $this->setSchoolSetting($schoolId, 'academy_setup_progress', json_encode($progress));
        $this->setSchoolSetting($schoolId, 'academy_setup_status', '0');

        return $progress;
    }

    public function process(int $schoolId, array $payload, callable $updateCheckpoint): void
    {
        $school = School::on('mysql')->findOrFail($schoolId);
        $this->bootSchoolConnection($school->database_name);

        $selectedMediumMasterIds = collect($payload['medium_ids'] ?? [])->map(fn($id) => (int) $id)->filter()->values();
        $selectedSectionMasterIds = collect($payload['section_ids'] ?? [])->map(fn($id) => (int) $id)->filter()->values();
        $selectedStreamMasterIds = collect($payload['stream_ids'] ?? [])->map(fn($id) => (int) $id)->filter()->values();
        $selectedClassMasterIds = collect($payload['class_ids'] ?? [])->map(fn($id) => (int) $id)->filter()->values();
        $selectedSubjectMasterIds = collect($payload['subject_ids'] ?? [])->map(fn($id) => (int) $id)->filter()->values();

        SessionYear::whereNotNull('id')->where('school_id', $schoolId)->forceDelete();

        // 1) Session year + semesters
        $sessionYear = $this->upsertSessionYear($schoolId, $payload['session'] ?? []);
        $semesters = $this->upsertSemesters($schoolId, $sessionYear['id'], $payload['session']['semesters'] ?? []);
        $updateCheckpoint('session_year', 'done', [
            'session_year_id' => $sessionYear['id'],
            'semesters_count' => count($semesters),
        ]);

        SessionYear::where('school_id', $schoolId)->where('default', 1)->update([
            'default' => 0,
        ]);

        SessionYear::where('id', $sessionYear['id'])->update([
            'default' => 1,
        ]);

        // clear school cache
        $cache = app(CachingService::class);
        $cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'), $schoolId);

        $cache->removeSchoolCache(config("constants.CACHE.SCHOOL.SESSION_YEAR"), $schoolId);
        $cache->removeSchoolCache(config("constants.CACHE.SCHOOL.SEMESTER"), $schoolId);
        $cache->removeSchoolCache(config("constants.CACHE.SCHOOL.ALL_SESSION_YEARS"), $schoolId);

        // 2) Mediums + sections
        $mediums = $this->upsertMediums($schoolId, $selectedMediumMasterIds->all());
        $sections = $this->upsertSections($schoolId, $selectedSectionMasterIds->all());
        $updateCheckpoint('mediums_sections', 'done', [
            'mediums_count' => count($mediums),
            'sections_count' => count($sections),
        ]);

        // 3) Shifts
        $shifts = $this->upsertShifts($schoolId, $payload['shifts'] ?? []);
        $updateCheckpoint('shifts', 'done', [
            'shifts_count' => count($shifts),
        ]);

        // 4) Streams
        $streams = $this->upsertStreams($schoolId, $selectedStreamMasterIds->all());
        $updateCheckpoint('streams', 'done', [
            'streams_count' => count($streams),
        ]);

        // 5) Classes + sections + subjects + mapping (medium-wise)
        $mapping = $payload['mapping'] ?? [];
        $classMap = $this->upsertClassesAndSections(
            $schoolId,
            $selectedClassMasterIds->all(),
            $mediums,
            $streams,
            $shifts,
            $sections,
            $mapping
        );

        $subjectMap = $this->upsertSubjectsByMedium(
            $schoolId,
            $mediums,
            $selectedSubjectMasterIds->all()
        );

        $classSubjects = $this->upsertClassSubjectsAndElectives(
            $schoolId,
            $sessionYear['id'],
            $mapping,
            $classMap,
            $subjectMap,
            $semesters,
            $shifts
        );
        $updateCheckpoint('classes_subjects', 'done', [
            'classes_count' => count($classMap),
            'class_subjects_count' => count($classSubjects),
        ]);

        // 6) Syllabus generation + linkage
        $syllabusCount = $this->generateAndLinkSyllabus($classSubjects);
        $updateCheckpoint('syllabus', 'done', [
            'syllabus_count' => $syllabusCount,
        ]);

        // 7) Store Board mapping (main DB) if selected
        $boardId = (int) ($payload['session']['board_id'] ?? 0);
        if ($boardId > 0) {
            DB::connection('mysql')->table('school_boards')->updateOrInsert(
                ['school_id' => $schoolId, 'sch_board_id' => $boardId],
                ['school_id' => $schoolId, 'sch_board_id' => $boardId, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // 8) Complete
        $this->setSchoolSetting($schoolId, 'academy_setup_status', '1');
        $this->setSchoolSetting($schoolId, 'academy_setup_in_progress', '0');
        app(CachingService::class)->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'), $schoolId);
        $updateCheckpoint('completed', 'done', ['redirect' => route('dashboard')]);
    }

    public function markFailure(int $schoolId): void
    {
        $this->setSchoolSetting($schoolId, 'academy_setup_in_progress', '0');
    }

    private function upsertSessionYear(int $schoolId, array $session): array
    {
        $startDate = $session['start_date'] ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $endDate = $session['end_date'] ?? Carbon::now()->endOfYear()->format('Y-m-d');

        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));

        $row = DB::connection('school')->table('session_years')
            ->where('school_id', $schoolId)
            ->where('name', $session['name'] ?? '')
            ->first();

        if ($row) {
            DB::connection('school')->table('session_years')->where('id', $row->id)->update([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'updated_at' => now(),
            ]);
            return ['id' => (int) $row->id];
        }

        $id = DB::connection('school')->table('session_years')->insertGetId([
            'name' => $session['name'] ?? (Carbon::parse($startDate)->format('Y') . '-' . Carbon::parse($endDate)->format('Y')),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'default' => 1,
            'school_id' => $schoolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => (int) $id];
    }

    private function upsertSemesters(int $schoolId, int $sessionYearId, array $semesters): array
    {
        $saved = [];
        foreach ($semesters as $semester) {
            if (empty($semester['name'])) {
                continue;
            }

            $semester['start_date'] = date('Y-m-d', strtotime($semester['start_date'] ?? ''));
            $semester['end_date'] = date('Y-m-d', strtotime($semester['end_date'] ?? ''));

            $existing = DB::connection('school')->table('semesters')
                ->where('school_id', $schoolId)
                ->where('session_year_id', $sessionYearId)
                ->where('name', $semester['name'])
                ->first();

            if ($existing) {
                DB::connection('school')->table('semesters')->where('id', $existing->id)->update([
                    'start_date' => $semester['start_date'] ?? null,
                    'end_date' => $semester['end_date'] ?? null,
                    'updated_at' => now(),
                ]);
                $saved[] = ['id' => (int) $existing->id, 'name' => $semester['name']];
                continue;
            }

            $id = DB::connection('school')->table('semesters')->insertGetId([
                'name' => $semester['name'],
                'start_date' => $semester['start_date'] ?? null,
                'end_date' => $semester['end_date'] ?? null,
                'school_id' => $schoolId,
                'session_year_id' => $sessionYearId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $saved[] = ['id' => (int) $id, 'name' => $semester['name']];
        }

        return $saved;
    }

    private function upsertMediums(int $schoolId, array $masterMediumIds): array
    {
        $masterMediums = SchMedium::on('mysql')->whereIn('id', $masterMediumIds)->where('status', 1)->get();
        $map = [];
        foreach ($masterMediums as $masterMedium) {
            DB::connection('school')->table('mediums')->updateOrInsert(
                ['school_id' => $schoolId, 'name' => $masterMedium->name],
                ['updated_at' => now(), 'created_at' => now()]
            );

            $existing = DB::connection('school')->table('mediums')
                ->where('school_id', $schoolId)
                ->where('name', $masterMedium->name)
                ->orderByDesc('id')
                ->value('id');

            $map[(int) $masterMedium->id] = (int) $existing;
        }
        return $map;
    }

    private function upsertSections(int $schoolId, array $masterSectionIds): array
    {
        $masterSections = SchSection::on('mysql')->whereIn('id', $masterSectionIds)->where('status', 1)->get();
        $map = [];
        foreach ($masterSections as $masterSection) {
            DB::connection('school')->table('sections')->updateOrInsert(
                ['school_id' => $schoolId, 'name' => $masterSection->name],
                ['updated_at' => now(), 'created_at' => now()]
            );
            $map[(int) $masterSection->id] = (int) DB::connection('school')->table('sections')
                ->where('school_id', $schoolId)->where('name', $masterSection->name)->value('id');
        }
        return $map;
    }

    private function upsertShifts(int $schoolId, array $shifts): array
    {
        $ids = [];
        foreach ($shifts as $shift) {
            if (empty($shift['name']) || empty($shift['start_time']) || empty($shift['end_time'])) {
                continue;
            }

            DB::connection('school')->table('shifts')->updateOrInsert(
                [
                    'school_id' => $schoolId,
                    'name' => $shift['name'],
                ],
                [
                    'start_time' => $shift['start_time'],
                    'end_time' => $shift['end_time'],
                    'status' => (int) ($shift['status'] ?? 1),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $id = (int) DB::connection('school')->table('shifts')
                ->where('school_id', $schoolId)->where('name', $shift['name'])->value('id');
            $ids[$shift['name']] = $id;
        }
        return $ids;
    }

    private function upsertStreams(int $schoolId, array $masterStreamIds): array
    {
        $masterStreams = SchStream::on('mysql')->whereIn('id', $masterStreamIds)->where('status', 1)->get();
        $map = [];
        foreach ($masterStreams as $masterStream) {
            DB::connection('school')->table('streams')->updateOrInsert(
                ['school_id' => $schoolId, 'name' => $masterStream->name],
                ['updated_at' => now(), 'created_at' => now()]
            );
            $map[(int) $masterStream->id] = (int) DB::connection('school')->table('streams')
                ->where('school_id', $schoolId)->where('name', $masterStream->name)->value('id');
        }
        return $map;
    }

    private function upsertClassesAndSections(
        int $schoolId,
        array $masterClassIds,
        array $mediumMap,
        array $streamMap,
        array $shiftMap,
        array $sectionMap,
        array $mapping
    ): array {
        $masterClasses = SchClass::on('mysql')->whereIn('id', $masterClassIds)->where('status', 1)->get();
        $classMap = [];
        $schoolMediumIds = DB::connection('school')->table('mediums')->where('school_id', $schoolId)->pluck('id')->map(fn($id) => (int) $id)->all();
        $schoolStreamIds = DB::connection('school')->table('streams')->where('school_id', $schoolId)->pluck('id')->map(fn($id) => (int) $id)->all();
        $schoolShiftIds = DB::connection('school')->table('shifts')->where('school_id', $schoolId)->pluck('id')->map(fn($id) => (int) $id)->all();
        $schoolSectionIds = DB::connection('school')->table('sections')->where('school_id', $schoolId)->pluck('id')->map(fn($id) => (int) $id)->all();

        foreach ($mediumMap as $masterMediumId => $schoolMediumId) {
            if (!$schoolMediumId || !in_array((int) $schoolMediumId, $schoolMediumIds, true)) {
                throw new RuntimeException("Mapped school medium id is missing for master medium id {$masterMediumId}");
            }

            foreach ($masterClasses as $masterClass) {
                // Determine mapped shifts (for fallback if no streams)
                $selectedShiftNames = $mapping[$masterMediumId][$masterClass->id]['shift_ids'] ?? [];
                if (empty($selectedShiftNames)) {
                    $selectedShiftNames = [null]; // null shift implies general no-shift
                }

                // Determine mapped streams
                $selectedStreamIds = collect($mapping[$masterMediumId][$masterClass->id]['stream_ids'] ?? [0])
                    ->map(fn($id) => (int) $id)
                    ->filter()
                    ->values()
                    ->all();
                if (empty($selectedStreamIds)) {
                    $selectedStreamIds = [0];
                }

                $streamShifts = $mapping[$masterMediumId][$masterClass->id]['stream_shifts'] ?? [];

                foreach ($selectedStreamIds as $masterStreamId) {
                    $schoolStreamId = $masterStreamId > 0 ? ($streamMap[$masterStreamId] ?? null) : null;
                    if ($masterStreamId > 0 && (!$schoolStreamId || !in_array((int) $schoolStreamId, $schoolStreamIds, true))) {
                        throw new RuntimeException("Mapped school stream id is missing for master stream id {$masterStreamId}");
                    }

                    // Extract semester enabling for this exact stream
                    $streamConfig = $mapping[$masterMediumId][$masterClass->id]['stream_configs'][$masterStreamId] ?? [];
                    $enableSemesters = !empty($streamConfig['enable_semesters']);

                    // Lookup mapped shift exactly for this stream
                    $mappedShiftName = null;
                    if ($masterStreamId > 0 && !empty($streamShifts[$masterStreamId])) {
                        $mappedShiftName = $streamShifts[$masterStreamId];
                    }

                    // If no streams selected, fallback to globally assigned shifts for the class
                    $shiftsToProcess = $masterStreamId > 0 ? [$mappedShiftName] : $selectedShiftNames;

                    foreach ($shiftsToProcess as $shiftName) {
                        $schoolShiftId = $shiftName ? ($shiftMap[$shiftName] ?? null) : null;
                        if ($shiftName && (!$schoolShiftId || !in_array((int) $schoolShiftId, $schoolShiftIds, true))) {
                            throw new RuntimeException("Mapped school shift id is missing for shift {$shiftName}");
                        }

                        DB::connection('school')->table('classes')->updateOrInsert(
                            [
                                'school_id' => $schoolId,
                                'name' => $masterClass->name,
                                'medium_id' => $schoolMediumId,
                                'stream_id' => $schoolStreamId,
                                'shift_id' => $schoolShiftId
                            ],
                            [
                                'include_semesters' => $enableSemesters ? 1 : 0,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );

                        $schoolClassId = (int) DB::connection('school')->table('classes')
                            ->where('school_id', $schoolId)
                            ->where('name', $masterClass->name)
                            ->where('medium_id', $schoolMediumId)
                            ->where('stream_id', $schoolStreamId)
                            ->where('shift_id', $schoolShiftId)
                            ->value('id');

                        $key = $this->classMapKey((int) $masterClass->id, (int) $masterMediumId, $masterStreamId, $schoolShiftId);
                        $classMap[$key] = $schoolClassId;

                        foreach ($sectionMap as $masterSectionId => $schoolSectionId) {
                            if (!$schoolSectionId || !in_array((int) $schoolSectionId, $schoolSectionIds, true)) {
                                throw new RuntimeException("Mapped school section id is missing for master section id {$masterSectionId}");
                            }

                            DB::connection('school')->table('class_sections')->updateOrInsert(
                                [
                                    'class_id' => $schoolClassId,
                                    'section_id' => $schoolSectionId,
                                    'medium_id' => $schoolMediumId,
                                ],
                                [
                                    'school_id' => $schoolId,
                                    'updated_at' => now(),
                                    'created_at' => now(),
                                    'deleted_at' => null,
                                ]
                            );
                        }
                    }
                }
            }
        }

        return $classMap;
    }

    private function upsertSubjectsByMedium(int $schoolId, array $mediumMap, array $masterSubjectIds): array
    {
        $masterSubjects = SchSubject::on('mysql')->whereIn('id', $masterSubjectIds)->where('status', 1)->get();
        $map = [];
        $schoolMediumIds = DB::connection('school')->table('mediums')->where('school_id', $schoolId)->pluck('id')->map(fn($id) => (int) $id)->all();

        foreach ($mediumMap as $masterMediumId => $schoolMediumId) {
            if (!$schoolMediumId || !in_array((int) $schoolMediumId, $schoolMediumIds, true)) {
                throw new RuntimeException("Cannot create subjects: school medium id missing for master medium id {$masterMediumId}");
            }

            foreach ($masterSubjects as $masterSubject) {
                DB::connection('school')->table('subjects')->updateOrInsert(
                    [
                        'school_id' => $schoolId,
                        'name' => $masterSubject->name,
                        'medium_id' => $schoolMediumId,
                        'type' => $masterSubject->type,
                    ],
                    [
                        'code' => $masterSubject->code,
                        'bg_color' => $masterSubject->bg_color,
                        'image' => $masterSubject->getRawOriginal('image') ?? '',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $subjectId = (int) DB::connection('school')->table('subjects')
                    ->where('school_id', $schoolId)
                    ->where('name', $masterSubject->name)
                    ->where('medium_id', $schoolMediumId)
                    ->where('type', $masterSubject->type)
                    ->value('id');

                $map[$masterMediumId][$masterSubject->id] = $subjectId;
            }
        }

        return $map;
    }

    private function upsertClassSubjectsAndElectives(
        int $schoolId,
        int $sessionYearId,
        array $mapping,
        array $classMap,
        array $subjectMap,
        array $semesters,
        array $shiftMap
    ): array {
        $classSubjects = [];
        $semesterIdMap = collect($semesters)->pluck('id')->values()->all();
        $validClassIds = DB::connection('school')->table('classes')->where('school_id', $schoolId)->pluck('id')->map(fn($id) => (int) $id)->all();
        $validSubjectIds = DB::connection('school')->table('subjects')->where('school_id', $schoolId)->pluck('id')->map(fn($id) => (int) $id)->all();

        foreach ($mapping as $masterMediumId => $mediumClassMapping) {
            foreach ($mediumClassMapping as $masterClassId => $classConfig) {
                $shiftNames = $classConfig['shift_ids'] ?? [];
                if (empty($shiftNames)) {
                    $shiftNames = [null];
                }

                $streamIds = collect($classConfig['stream_ids'] ?? [0])->map(fn($id) => (int) $id)->filter()->values()->all();
                if (empty($streamIds)) {
                    $streamIds = [0];
                }

                $streamShifts = $classConfig['stream_shifts'] ?? [];

                foreach ($streamIds as $masterStreamId) {
                    $streamConfig = $classConfig['stream_configs'][$masterStreamId] ?? [];
                    $enableSemesters = !empty($streamConfig['enable_semesters']);

                    $mappedShiftName = null;
                    if ($masterStreamId > 0 && !empty($streamShifts[$masterStreamId])) {
                        $mappedShiftName = $streamShifts[$masterStreamId];
                    }

                    $shiftsToProcess = $masterStreamId > 0 ? [$mappedShiftName] : $shiftNames;

                    foreach ($shiftsToProcess as $shiftName) {
                        $schoolShiftId = $shiftName ? ($shiftMap[$shiftName] ?? null) : null;
                        $classKey = $this->classMapKey((int) $masterClassId, (int) $masterMediumId, (int) $masterStreamId, $schoolShiftId);

                        $schoolClassId = $classMap[$classKey] ?? null;
                        if (!$schoolClassId || !in_array((int) $schoolClassId, $validClassIds, true)) {
                            continue;
                        }

                        $subjectsBySemester = [];

                        if ($enableSemesters) {
                            $mappedSemesters = $streamConfig['semesters'] ?? [];
                            foreach ($mappedSemesters as $semesterName => $mappedSubjects) {
                                $schoolSemesterId = collect($semesters)->firstWhere('name', $semesterName)['id'] ?? null;
                                if ($schoolSemesterId && in_array($schoolSemesterId, $semesterIdMap, true)) {
                                    $subjectsBySemester[(int) $schoolSemesterId] = collect($mappedSubjects)->map(fn($id) => (int) $id)->filter()->values()->all();
                                }
                            }
                        } else {
                            $mappedSubjects = collect($streamConfig['subjects'] ?? [])->map(fn($id) => (int) $id)->filter()->values()->all();
                            $subjectsBySemester[0] = $mappedSubjects;
                        }

                        foreach ($subjectsBySemester as $semesterIdKey => $compulsorySubjects) {
                            $semesterId = $semesterIdKey === 0 ? null : $semesterIdKey;

                            foreach ($compulsorySubjects as $masterSubjectId) {
                                $schoolSubjectId = $subjectMap[$masterMediumId][$masterSubjectId] ?? null;
                                if (!$schoolSubjectId || !in_array((int) $schoolSubjectId, $validSubjectIds, true)) {
                                    continue;
                                }

                                DB::connection('school')->table('class_subjects')->updateOrInsert(
                                    [
                                        'class_id' => $schoolClassId,
                                        'subject_id' => $schoolSubjectId,
                                        'semester_id' => $semesterId,
                                        'session_year_id' => $sessionYearId,
                                    ],
                                    [
                                        'type' => 'Compulsory',
                                        'elective_subject_group_id' => null,
                                        'school_id' => $schoolId,
                                        'updated_at' => now(),
                                        'created_at' => now(),
                                    ]
                                );

                                $classSubjects[] = DB::connection('school')->table('class_subjects')
                                    ->where('class_id', $schoolClassId)
                                    ->where('subject_id', $schoolSubjectId)
                                    ->where('semester_id', $semesterId)
                                    ->where('session_year_id', $sessionYearId)
                                    ->first();
                            }
                        }

                        // Handle elective groups locally mapped to this stream (if user passed any)
                        // Currently our UI only assigns mandatory subjects stream-wise. Elective groups logic could be extended.
                        foreach (($streamConfig['elective_groups'] ?? []) as $group) {
                            $groupSubjects = collect($group['subject_ids'] ?? [])->map(fn($id) => (int) $id)->filter()->values()->all();
                            if (empty($groupSubjects)) {
                                continue;
                            }

                            // Identify semester_id if configured dynamically
                            $groupSemesterId = null;
                            if ($enableSemesters && !empty($group['semester_name'])) {
                                $groupSemesterId = collect($semesters)->firstWhere('name', $group['semester_name'])['id'] ?? null;
                            }

                            $groupId = DB::connection('school')->table('elective_subject_groups')->insertGetId([
                                'total_subjects' => count($groupSubjects),
                                'total_selectable_subjects' => (int) ($group['total_selectable_subjects'] ?? 1),
                                'class_id' => $schoolClassId,
                                'semester_id' => $groupSemesterId,
                                'session_year_id' => $sessionYearId,
                                'school_id' => $schoolId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            foreach ($groupSubjects as $masterSubjectId) {
                                $schoolSubjectId = $subjectMap[$masterMediumId][$masterSubjectId] ?? null;
                                if (!$schoolSubjectId || !in_array((int) $schoolSubjectId, $validSubjectIds, true)) {
                                    continue; // Ignore invalid subjects mapped in elective
                                }

                                DB::connection('school')->table('class_subjects')->updateOrInsert(
                                    [
                                        'class_id' => $schoolClassId,
                                        'subject_id' => $schoolSubjectId,
                                        'semester_id' => $groupSemesterId,
                                        'session_year_id' => $sessionYearId,
                                    ],
                                    [
                                        'type' => 'Elective',
                                        'elective_subject_group_id' => $groupId,
                                        'school_id' => $schoolId,
                                        'updated_at' => now(),
                                        'created_at' => now(),
                                    ]
                                );

                                $classSubjects[] = DB::connection('school')->table('class_subjects')
                                    ->where('class_id', $schoolClassId)
                                    ->where('subject_id', $schoolSubjectId)
                                    ->whereNull('semester_id')
                                    ->where('session_year_id', $sessionYearId)
                                    ->first();
                            }
                        }
                    }
                }
            }
        }

        return array_values(array_filter($classSubjects));
    }

    private function generateAndLinkSyllabus(array $classSubjects): int
    {
        $count = 0;
        foreach ($classSubjects as $classSubject) {
            $class = DB::connection('school')->table('classes')->where('id', $classSubject->class_id)->first();
            $subject = DB::connection('school')->table('subjects')->where('id', $classSubject->subject_id)->first();
            if (!$class || !$subject) {
                continue;
            }

            $type = !empty($subject->type) ? $subject->type : 'General';
            $title = trim("{$class->name} - {$subject->name} - {$type}");

            DB::connection('school')->table('syllabus')->updateOrInsert(
                [
                    'title' => $title,
                ],
                [
                    'class_id' => $classSubject->class_id,
                    'subject_id' => $classSubject->subject_id,
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $syllabusId = (int) DB::connection('school')->table('syllabus')
                ->where('class_id', $classSubject->class_id)
                ->where('subject_id', $classSubject->subject_id)
                ->where('title', $title)
                ->value('id');

            DB::connection('school')->table('class_subjects')
                ->where('id', $classSubject->id)
                ->update(['syllabus_id' => $syllabusId, 'updated_at' => now()]);

            $count++;
        }

        return $count;
    }

    private function setSchoolSetting(int $schoolId, string $name, string $value): void
    {
        SchoolSetting::on('school')->updateOrCreate(
            ['school_id' => $schoolId, 'name' => $name],
            ['data' => $value, 'type' => 'string']
        );
    }

    private function classMapKey(int $classId, int $mediumId, ?int $streamId, ?int $shiftId = null): string
    {
        return $classId . ':' . $mediumId . ':' . ($streamId ?? 0) . ':' . ($shiftId ?? 0);
    }

    private function bootSchoolConnection(string $databaseName): void
    {
        config(['database.connections.school.database' => $databaseName]);
        DB::purge('school');
        DB::connection('school')->reconnect();
        DB::setDefaultConnection('school');
    }
}
