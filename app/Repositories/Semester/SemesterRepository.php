<?php

namespace App\Repositories\Semester;

use App\Models\Semester;
use App\Repositories\Saas\SaaSRepository;
use App\Services\CachingService;
use Carbon\Carbon;

class SemesterRepository extends SaaSRepository implements SemesterInterface
{

    public function __construct(Semester $model)
    {
        parent::__construct($model);
    }

    public function default($schoolId = null)
    {
        $cache = app(CachingService::class);

        // 1. Determine which session year we are viewing
        $viewingSessionYear = $cache->getSessionYear();
        $defaultSessionYear = $cache->getDefaultSessionYear($schoolId);

        if (!$viewingSessionYear) {
            return null;
        }

        $currentDate = Carbon::now()->format('Y-m-d');

        // 2. Try to find the semester active TODAY for the viewing session year
        $currentSemester = $this->builder()
            ->where('session_year_id', $viewingSessionYear->id)
            ->whereDate('start_date', '<=', $currentDate)
            ->whereDate('end_date', '>=', $currentDate)
            ->orderBy('start_date', 'desc')
            ->first();

        if ($currentSemester) {
            return $currentSemester;
        }

        // 3. Fallback logic: 
        // If it's the default session year, we don't force a fallback (per user request)
        $viewingID = (string)$viewingSessionYear->id;
        $defaultID = $defaultSessionYear ? (string)$defaultSessionYear->id : null;

        if ($defaultID !== null && $viewingID === $defaultID) {
            return null;
        }

        // For non-default session years, fallback only if the session has already started
        if (Carbon::now()->lt($viewingSessionYear->getRawOriginal('start_date'))) {
            return null;
        }

        // Fallback to the first semester
        return $this->builder()->withTrashed()
            ->where('session_year_id', $viewingSessionYear->id)
            ->orderBy('start_date', 'asc')
            ->first();
    }
}
