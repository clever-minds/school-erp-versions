<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAcademySetupWizardJob;
use App\Models\SchBoard;
use App\Models\SchClass;
use App\Models\SchMedium;
use App\Models\SchSection;
use App\Models\SchoolSetting;
use App\Models\SchStream;
use App\Models\SchSubject;
use App\Services\AcademySetupWizardService;
use App\Services\CachingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AcademySetupWizardController extends Controller
{
    public function __construct(
        private readonly CachingService $cache,
        private readonly AcademySetupWizardService $setupService
    ) {}

    public function index()
    {

        if (!Auth::check() || !Auth::user()->hasRole('School Admin') || !Auth::user()->school_id) {
            return redirect()->route('dashboard')->withErrors('You are not authorized to access this route.');
        }

        $schoolId = Auth::user()->school_id;
        $isCompleted = (int) $this->cache->getSchoolSettings('academy_setup_status') === 1;
        $isAcademySetupWizardEnabled = (int) $this->cache->getSystemSettings('academy_master_status') === 1;
        if ($isCompleted || !$isAcademySetupWizardEnabled) {
            return redirect()->route('dashboard');
        }

        $masterData = [
            'boards' => SchBoard::on('mysql')->where('status', 1)->orderBy('name')->get(['id', 'name', 'code']),
            'mediums' => SchMedium::on('mysql')->where('status', 1)->orderBy('name')->get(['id', 'name']),
            'sections' => SchSection::on('mysql')->where('status', 1)->orderBy('sort_order')->get(['id', 'name']),
            'streams' => SchStream::on('mysql')->where('status', 1)->orderBy('name')->get(['id', 'name']),
            'classes' => SchClass::on('mysql')->where('status', 1)->orderBy('sort_order')->get(['id', 'name']),
            'subjects' => SchSubject::on('mysql')->where('status', 1)->orderBy('name')->get(['id', 'name', 'type', 'code']),
        ];

        $progress = $this->getProgressData($schoolId);
        $runId = $this->cache->getSchoolSettings('academy_setup_run_id');

        return view('academy-setup-wizard.index', [
            'masterData' => $masterData,
            'progress' => $progress,
            'runId' => $runId,
            'reverb' => [
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => config('broadcasting.connections.reverb.options.port'),
                'scheme' => config('broadcasting.connections.reverb.options.scheme', 'http'),
            ],
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $sessionData = $request->input('session', []);
        $enableSemesters = filter_var($sessionData['enable_semesters'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $rules = [
            'session.name' => 'required|string',
            'session.start_date' => 'required|date',
            'session.end_date' => 'required|date|after:session.start_date',
            'medium_ids' => 'required|array|min:1',
            'class_ids' => 'required|array|min:1',
            'subject_ids' => 'required|array|min:1',
            'mapping' => 'required|array',
            'shifts' => 'required|array|min:1',
            'shifts.*.name' => 'required|string',
            'shifts.*.start_time' => 'required',
            'shifts.*.end_time' => 'required',
        ];

        if ($enableSemesters) {
            $rules['session.semesters'] = 'required|array|min:1';
            $rules['session.semesters.*.name'] = 'required|string';
            $rules['session.semesters.*.start_date'] = 'required|date';
            $rules['session.semesters.*.end_date'] = 'required|date|after:session.semesters.*.start_date';
        }

        $validator = Validator::make($request->all(), $rules, [
            'medium_ids.min' => __('Please select at least one medium'),
            'class_ids.min' => __('Please select at least one class'),
            'subject_ids.min' => __('Please select at least one subject'),
            'session.semesters.*.end_date.after' => __('Semester end date must be greater than start date.'),
        ]);

        $validator->after(function ($validator) use ($request, $enableSemesters) {
            if ($enableSemesters) {
                $session = $request->input('session');
                $sessionStart = strtotime($session['start_date']);
                $sessionEnd = strtotime($session['end_date']);
                $semesters = $session['semesters'] ?? [];

                foreach ($semesters as $index => $sem) {
                    $semStart = strtotime($sem['start_date']);
                    $semEnd = strtotime($sem['end_date']);

                    // Within Session range
                    if ($semStart < $sessionStart || $semStart > $sessionEnd) {
                        $validator->errors()->add("session.semesters.$index.start_date", __('Semester dates must fall within the selected session year.'));
                    }
                    if ($semEnd < $sessionStart || $semEnd > $sessionEnd) {
                        $validator->errors()->add("session.semesters.$index.end_date", __('Semester dates must fall within the selected session year.'));
                    }
                }

                // Overlap & Chronological Check
                $count = count($semesters);
                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $startA = strtotime($semesters[$i]['start_date']);
                        $endA = strtotime($semesters[$i]['end_date']);
                        $startB = strtotime($semesters[$j]['start_date'] ?? '');
                        $endB = strtotime($semesters[$j]['end_date'] ?? '');

                        if ($startA && $endA && $startB && $endB) {
                            // Overlap check
                            if ($startA <= $endB && $endA >= $startB) {
                                $validator->errors()->add("session.semesters.$j.start_date", __('Semester date range overlaps with another semester.'));
                            }

                            // Strict Gap Rule (next starts after prev ends)
                            if ($j === $i + 1 && $startB <= $endA) {
                                $validator->errors()->add("session.semesters.$j.start_date", __("Semester :next start date must be after Semester :prev end date.", [
                                    'next' => $j + 1,
                                    'prev' => $i + 1
                                ]));
                            }
                        }
                    }
                }
            }

            // Shifts Validation
            $shifts = $request->input('shifts', []);
            foreach ($shifts as $index => $shift) {
                if (!empty($shift['start_time']) && !empty($shift['end_time'])) {
                    if (strtotime($shift['start_time']) >= strtotime($shift['end_time'])) {
                        $validator->errors()->add("shifts.$index.end_time", __("Shift ':name' end time must be after start time.", ['name' => $shift['name']]));
                    }
                }
            }

            // Mutual Exclusivity Check (Core vs Elective)
            $mapping = $request->input('mapping', []);
            $allSubjects = SchSubject::on('mysql')->whereIn('id', $request->input('subject_ids', []))->get(['id', 'name'])->keyBy('id');

            foreach ($mapping as $mediumId => $classes) {
                foreach ($classes as $classId => $classConfig) {
                    $streamConfigs = $classConfig['stream_configs'] ?? [];
                    foreach ($streamConfigs as $streamId => $config) {
                        $enableSem = filter_var($config['enable_semesters'] ?? false, FILTER_VALIDATE_BOOLEAN);
                        $enableElective = filter_var($config['enable_elective_groups'] ?? false, FILTER_VALIDATE_BOOLEAN);
                        $electiveGroups = $config['elective_groups'] ?? [];

                        if ($enableSem) {
                            $semestersMapped = $config['semesters'] ?? [];
                            foreach ($semestersMapped as $semName => $coreSubjects) {
                                $electiveSubjects = [];
                                if ($enableElective) {
                                    foreach ($electiveGroups as $group) {
                                        if (($group['semester_name'] ?? null) === $semName) {
                                            $groupSubjects = $group['subject_ids'] ?? [];
                                            if (count($groupSubjects) < 2) {
                                                $validator->errors()->add("mapping", __("Each elective set must have at least 2 subjects."));
                                            }
                                            // Check for collisions between elective groups
                                            $groupIntersection = array_intersect($electiveSubjects, $groupSubjects);
                                            foreach ($groupIntersection as $sid) {
                                                $sname = $allSubjects->has($sid) ? $allSubjects->get($sid)->name : "Subject #$sid";
                                                $validator->errors()->add("mapping", __("The subject ':subject' is assigned to more than one elective set in :context.", ['subject' => $sname, 'context' => $semName]));
                                            }
                                            $electiveSubjects = array_merge($electiveSubjects, $groupSubjects);
                                        }
                                    }
                                }
                                $this->checkCollision($validator, $coreSubjects, $electiveSubjects, $allSubjects, $semName);
                            }
                        } else {
                            $coreSubjects = $config['subjects'] ?? [];
                            $electiveSubjects = [];
                            if ($enableElective) {
                                foreach ($electiveGroups as $group) {
                                    if (!($group['semester_name'] ?? null)) {
                                        $groupSubjects = $group['subject_ids'] ?? [];
                                        if (count($groupSubjects) < 2) {
                                            $validator->errors()->add("mapping", __("Each elective set must have at least 2 subjects."));
                                        }
                                        // Check for collisions between elective groups
                                        $groupIntersection = array_intersect($electiveSubjects, $groupSubjects);
                                        foreach ($groupIntersection as $sid) {
                                            $sname = $allSubjects->has($sid) ? $allSubjects->get($sid)->name : "Subject #$sid";
                                            $validator->errors()->add("mapping", __("The subject ':subject' is assigned to more than one elective set.", ['subject' => $sname]));
                                        }
                                        $electiveSubjects = array_merge($electiveSubjects, $groupSubjects);
                                    }
                                }
                            }
                            $this->checkCollision($validator, $coreSubjects, $electiveSubjects, $allSubjects);
                        }
                    }
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $schoolId = Auth::user()->school_id;
        $inProgress = (int) $this->cache->getSchoolSettings('academy_setup_in_progress') === 1;
        if ($inProgress) {
            return response()->json([
                'error' => true,
                'message' => __('Setup is already running. Please wait for completion.'),
            ], 422);
        }

        $runId = (string) Str::uuid();

        ProcessAcademySetupWizardJob::dispatch(
            $schoolId,
            Auth::id(),
            $runId,
            $request->all()
        );

        return response()->json([
            'error' => false,
            'message' => __('Setup has started'),
            'data' => [
                'run_id' => $runId,
            ],
        ]);
    }

    public function progress(string $runId): JsonResponse
    {
        $schoolId = Auth::user()->school_id;
        $progress = $this->getProgressData($schoolId);
        $storedRunId = $this->cache->getSchoolSettings('academy_setup_run_id');

        if ($storedRunId && $storedRunId !== $runId) {
            return response()->json([
                'error' => false,
                'data' => [
                    'run_id' => $storedRunId,
                    'progress' => $progress,
                ],
            ]);
        }

        return response()->json([
            'error' => false,
            'data' => [
                'run_id' => $runId,
                'progress' => $progress,
            ],
        ]);
    }

    private function getProgressData(int $schoolId): array
    {
        $defaultProgress = $this->setupService->defaultProgress();

        $rawProgress = SchoolSetting::on('school')
            ->where('school_id', $schoolId)
            ->where('name', 'academy_setup_progress')
            ->value('data');

        if (!$rawProgress) {
            return $defaultProgress;
        }

        $decoded = json_decode($rawProgress, true);
        if (!is_array($decoded)) {
            return $defaultProgress;
        }

        return array_replace_recursive($defaultProgress, $decoded);
    }

    public function skipAcademySetupWizard()
    {
        $schoolId = Auth::user()->school_id;
        SchoolSetting::upsert([
            'school_id' => $schoolId,
            'name' => 'academy_setup_status',
            'data' => 1,
        ], ['school_id', 'name'], ['data']);
        $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'), $schoolId);
        return redirect()->route('dashboard');
    }

    private function checkCollision($validator, $coreSubjects, $electiveSubjects, $allSubjects, $context = null): void
    {
        if (empty($coreSubjects) || empty($electiveSubjects)) return;

        $intersection = array_intersect((array)$coreSubjects, (array)$electiveSubjects);
        foreach ($intersection as $subjectId) {
            $subjectName = $allSubjects->has($subjectId) ? $allSubjects->get($subjectId)->name : "Subject #$subjectId";
            $message = $context
                ? __("The subject ':subject' is already assigned as a Core subject and cannot be added as an Elective in :context.", ['subject' => $subjectName, 'context' => $context])
                : __("The subject ':subject' is already assigned as a Core subject and cannot be added as an Elective.", ['subject' => $subjectName]);

            $validator->errors()->add("mapping", $message);
        }
    }
}
