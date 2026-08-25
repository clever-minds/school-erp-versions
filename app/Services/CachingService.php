<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\Feature;
use App\Models\FormField;
use App\Models\Guidance;
use App\Models\LeaveMaster;
use App\Models\SessionYear;
use App\Repositories\Languages\LanguageInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\Semester\SemesterInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\SystemSetting\SystemSettingInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use stdClass;

class CachingService
{
    private static $requestCache = [];

    /**
     * @param $key
     * @param callable $callback - Callback function must return a value
     * @param int $time = 3600
     * @return mixed
     */
    public function systemLevelCaching($key, callable $callback, int $time = 3600)
    {
        return Cache::remember($key, $time, $callback);
    }

    /**
     * @param array|string $key
     * @return mixed|string
     */
    public function getSystemSettings(array|string $key = '*')
    {
        try {
            $systemSettings = app(SystemSettingInterface::class);
            $settings = $this->systemLevelCaching(config('constants.CACHE.SYSTEM.SETTINGS'), function () use ($systemSettings) {
                return $systemSettings->all()->pluck('data', 'name');
            });
            if (($key != '*')) {
                /* There is a minor possibility of getting a specific key from the $systemSettings
                * So I have not fetched Specific key from DB. Otherwise, Specific key will be fetched here
                * And it will be appended to the cached array here
                */
                $specificSettings = [];

                // If array is given in Key param
                if (is_array($key)) {
                    foreach ($key as $row) {
                        if ($settings && is_array($settings) && array_key_exists($row, $settings)) {
                            $specificSettings[$row] = $settings[$row] ?? '';
                        }
                    }
                    return $specificSettings;
                }

                // If String is given in Key param
                if ($settings && is_object($settings) && $settings->has($key)) {
                    return $settings[$key] ?? '';
                }

                return "";
            }
            return $settings;
        } catch (\Throwable $th) {
            return [];
        }
    }

    public function getLanguages()
    {
        $languages = app(LanguageInterface::class);
        return $this->systemLevelCaching(config('constants.CACHE.SYSTEM.LANGUAGE'), function () use ($languages) {
            return $languages->all();
        });
    }

    /**
     * @param $key
     * @param callable $callback
     * @param int|null $schoolId
     * @param int $time
     * @return mixed
     */
    public function schoolLevelCaching($key, callable $callback, $schoolId = null, int $time = 900)
    {
        if ($schoolId) {
            $key .= "_" . $schoolId;
        } else {
            $key .= "_" . (Auth::user() ? Auth::user()->school_id : null);
        }

        return Cache::remember($key, $time, $callback);
    }

    /**
     * @param array|string $key
     * @param int|null $schoolID
     * @return mixed|string
     */
    public function getSchoolSettings(array|string $key = '*', $schoolID = null)
    {
        $schoolSettings = app(SchoolSettingInterface::class);
        $schoolID = (!empty($schoolID)) ? $schoolID : Auth::user()->school_id;
        $settings = $this->schoolLevelCaching(config('constants.CACHE.SCHOOL.SETTINGS'), function () use ($schoolSettings, $schoolID) {
            return $schoolSettings->builder()->where('school_id', $schoolID)->get()->pluck('data', 'name');
        }, $schoolID);
        if (($key[0] != '*')) {
            /* There is a minor possibility of getting a specific key from the $systemSettings
             * So I have not fetched Specific key from DB. Otherwise, Specific key will be fetched here
             * And it will be appended to the cached array here
             */

            // If array is given in Key param
            if (is_array($key)) {
                $specificSettings = new stdClass();
                foreach ($key as $row) {
                    if ($settings && is_object($settings) && $settings->has($row)) {
                        $specificSettings->$row = $settings->get($row) ?? '';
                    }
                }
                return $specificSettings;
            }

            // If String is given in Key param
            if ($settings && is_object($settings) && $settings->has($key)) {
                return $settings->get($key);
            }

            return "";
        }
        return $settings;
    }

    public function removeSchoolCache($key, $schoolID = null)
    {
        if ($schoolID) {
            $key .= "_" . $schoolID;
        } else {
            $key .= "_" . Auth::user()->school_id;
        }

        Cache::forget($key);
    }

    public function removeSystemCache($key)
    {
        Cache::forget($key);
    }

    /**
     * @param int|null $schoolId
     * @return mixed
     */
    public function getDefaultSessionYear($schoolId = null)
    {
        $schoolId = (!empty($schoolId)) ? $schoolId : (Auth::user() ? Auth::user()->school_id : null);
        $cacheKey = "defaultSessionYear_" . ($schoolId ?? 'null');

        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }

        $sessionYear = app(SessionYearInterface::class);
        $data = $this->schoolLevelCaching(config('constants.CACHE.SCHOOL.SESSION_YEAR'), function () use ($sessionYear, $schoolId) {
            return $sessionYear->default($schoolId);
        }, $schoolId);

        self::$requestCache[$cacheKey] = $data;
        return $data;
    }

    public function getSessionYear()
    {

        if (DB::getDefaultConnection() == 'school') {
            if (isset(self::$requestCache['sessionYear'])) {
                return self::$requestCache['sessionYear'];
            }

            if (session()->has('session_year_id')) {
                $sessionYearId = session()->get('session_year_id');
                try {
                    $data = app(SessionYearInterface::class)->findById($sessionYearId);
                    self::$requestCache['sessionYear'] = $data;
                    return $data;
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    session()->forget('session_year_id');
                    session()->save();
                }
            }
            $data = $this->getDefaultSessionYear();
            self::$requestCache['sessionYear'] = $data;
            return $data;
        }
        return null;
    }

    public function setSessionYear($sessionYearId)
    {
        session()->put('session_year_id', $sessionYearId);
        session()->save();

        // Clear request-level caches
        self::$requestCache = [];

        // Reset semester when session year changes to ensure it's valid for the new year
        session()->forget('semester_id');
    }

    public function getSemester()
    {
        if (isset(self::$requestCache['semester'])) {
            return self::$requestCache['semester'];
        }

        if (session()->has('semester_id')) {
            $semesterId = session()->get('semester_id');
            try {
                $semester = app(SemesterInterface::class)->findById($semesterId);
                if ($semester) {
                    self::$requestCache['semester'] = $semester;
                    return $semester;
                }
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                session()->forget('semester_id');
                session()->save();
            }
        }
        $data = $this->getDefaultSemesterData();
        self::$requestCache['semester'] = $data;
        return $data;
    }

    public function setSemester($semesterId)
    {
        if (!$semesterId) {
            session()->forget('semester_id');
        } else {
            session()->put('semester_id', $semesterId);
        }
        session()->save();

        // Clear request-level caches
        self::$requestCache = [];
    }

    public function getSemestersBySessionYear($sessionYearId)
    {
        $semester = app(SemesterInterface::class);
        return $this->schoolLevelCaching(config('constants.CACHE.SCHOOL.GET_SEMESTERS_BY_SESSION_YEAR'), function () use ($semester, $sessionYearId) {
            return $semester->builder()->where('session_year_id', $sessionYearId)->get();
        }, $sessionYearId);
    }

    /**
     * Run a callback with the session year forced to the school's default,
     * regardless of what the user has selected in the "Viewing" dropdown.
     *
     * Use this in controller methods that serve FORM pages / dropdowns so that
     * form dropdowns always show data for the current (default) session year,
     * not the year the user is currently filtering tables by.
     *
     * @param  callable $callback
     * @return mixed
     */
    public function withDefaultSessionYear(callable $callback): mixed
    {
        $original = session('session_year_id');         // may be null
        $defaultYear = $this->getDefaultSessionYear();
        session(['session_year_id' => $defaultYear->id]);

        try {
            return $callback();
        } finally {
            // Restore original state
            if ($original === null) {
                session()->forget('session_year_id');
            } else {
                session(['session_year_id' => $original]);
            }
        }
    }
    public function getDefaultSemesterData($schoolId = null)
    {
        $schoolId = (!empty($schoolId)) ? $schoolId : (Auth::user() ? Auth::user()->school_id : null);
        $cacheKey = "defaultSemesterData_" . ($schoolId ?? 'null');

        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }

        $semester = app(SemesterInterface::class);
        $timetable = $this->schoolLevelCaching(config('constants.CACHE.SCHOOL.SEMESTER'), function () use ($semester, $schoolId) {
            return $semester->default($schoolId);
        }, $schoolId);

        /*Added empty values so that wherever the code is used, we don't need to add isset over there*/
        $data = empty($timetable) ? (object)[
            'id' => null,
            "name" => null,
            "start_month" => null,
            "end_month" => null,
            "school_id" => null,
            "created_at" => null,
            "updated_at" => null,
            "deleted_at" => null,
        ] : $timetable;

        self::$requestCache[$cacheKey] = $data;
        return $data;
    }

    public function getFeatures()
    {
        return $this->systemLevelCaching(config('constants.CACHE.SYSTEM.FEATURES'), function () {
            return Feature::activeFeatures()->get();
        });
    }

    public function getSchoolExtraFields()
    {
        return $this->systemLevelCaching(config('constants.CACHE.SYSTEM.SCHOOL_CUSTOM_FORM_FIELDS'), function () {
            return FormField::orderBy('rank')->get();
        });
    }

    public function getGuidances()
    {
        return $this->systemLevelCaching(config('constants.CACHE.SYSTEM.GUIDANCES'), function () {
            return Guidance::all();
        });
    }

    public function getSystemFaqs()
    {
        return $this->systemLevelCaching(config('constants.CACHE.SYSTEM.FAQS'), function () {
            return Faq::where('school_id', null)->get();
        });
    }

    public function getDefaultSessionYearLeaveMaster($schoolId = null)
    {
        $schoolId = $schoolId ?? Auth::user()->school_id;
        $sessionYear = $this->getSessionYear();
        return $this->schoolLevelCaching(config('constants.CACHE.SCHOOL.LEAVE_MASTER'), function () use ($schoolId, $sessionYear) {
            return LeaveMaster::where('school_id', $schoolId)->where('session_year_id', $sessionYear->id)->first();
        }, $schoolId);
    }

    public function getAllSessionYears($schoolId = null)
    {
        $schoolId = $schoolId ?? Auth::user()->school_id;
        return $this->schoolLevelCaching(config('constants.CACHE.SCHOOL.ALL_SESSION_YEARS'), function () use ($schoolId) {
            return SessionYear::where('school_id', $schoolId)->get();
        }, $schoolId);
    }
}
