<?php

namespace App\Traits;

use App\Services\CachingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

trait DateFormatTrait
{
    /**
     * Format a date/time value according to school settings
     *
     * @param mixed $value
     * @param string $key
     * @return string
     */
    protected function formatDateValue($value, $key = null)
    {
        if (!$value) {
            return $value;
        }

        try {
            // Convert to Carbon instance if it's not already
            if (!($value instanceof Carbon)) {
                $value = Carbon::parse($value); // Treat DB time as UTC
            }

            $cache = app(CachingService::class);

            // Get school_id from model if available (for queue jobs where Auth::user() is null)
            $schoolId = null;
            if (isset($this->school_id)) {
                $schoolId = $this->school_id;
            } elseif (method_exists($this, 'getAttribute') && $this->getAttribute('school_id')) {
                $schoolId = $this->getAttribute('school_id');
            } elseif (Auth::check() && Auth::user()) {
                $schoolId = Auth::user()->school_id;
            }

            $schoolSettings = $cache->getSchoolSettings('*', $schoolId);

            $systemSettings = $cache->getSystemSettings();

            // Get the date and time format from school settings, fallback to system settings, then to default
            $date_format = $schoolSettings['date_format'] ?? $systemSettings['date_format'] ?? 'Y-m-d';
            $time_format = $schoolSettings['time_format'] ?? $systemSettings['time_format'] ?? 'H:i:s';


            return $value->format(trim($date_format . ' ' . $time_format));
        } catch (\Exception $e) {
            return $value;
        }
    }

    protected function formatTimeOnly($value)
    {
        if (!$value) {
            return $value;
        }

        try {
            // Convert to Carbon instance if it's not already
            if (!($value instanceof Carbon)) {
                // $value = Carbon::parse($value); // Treat DB time as UTC
            }

            $cache = app(CachingService::class);

            // Get school_id from model if available (for queue jobs where Auth::user() is null)
            $schoolId = null;
            if (isset($this->school_id)) {
                $schoolId = $this->school_id;
            } elseif (method_exists($this, 'getAttribute') && $this->getAttribute('school_id')) {
                $schoolId = $this->getAttribute('school_id');
            } elseif (Auth::check() && Auth::user()) {
                $schoolId = Auth::user()->school_id;
            }

            $schoolSettings = $cache->getSchoolSettings('*', $schoolId);
            $systemSettings = $cache->getSystemSettings();

            $time_format = $schoolSettings['time_format'] ?? $systemSettings['time_format'] ?? 'H:i:s';

            return date($time_format, strtotime($value));

            return $value->format($time_format);
        } catch (\Exception $e) {
            return $value;
        }
    }

    protected function formatDateOnly($value)
    {
        if (!$value) {
            return $value;
        }


        // $value = Carbon::parse($value); // Treat DB time as UTC
        if (!($value instanceof Carbon)) {
            // $value = Carbon::parse($value); // Treat DB time as UTC
        }

        $cache = app(CachingService::class);

        // Get school_id from model if available (for queue jobs where Auth::user() is null)
        $schoolId = null;
        if (isset($this->school_id)) {
            $schoolId = $this->school_id;
        } elseif (method_exists($this, 'getAttribute') && $this->getAttribute('school_id')) {
            $schoolId = $this->getAttribute('school_id');
        } elseif (Auth::check() && Auth::user()) {
            $schoolId = Auth::user()->school_id;
        }

        $schoolSettings = $cache->getSchoolSettings('*', $schoolId);
        $systemSettings = $cache->getSystemSettings();

        $date_format = $schoolSettings['date_format'] ?? $systemSettings['date_format'] ?? 'Y-m-d';

        return date($date_format, strtotime($value));
        \Log::info([$value, $date_format]);
        // return $value->format($date_format);
    }
    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = parent::toArray();

        // Format all date fields using raw original values to avoid double formatting
        // (accessors like getCreatedAtAttribute may have already formatted the value in $attributes)
        foreach ($this->getDates() as $key) {
            if (isset($attributes[$key])) {
                $rawValue = $this->getRawOriginal($key);
                $attributes[$key] = $this->formatDateValue($rawValue ?? $attributes[$key], $key);
            }
        }

        // Handle date aliases using raw original values to avoid double formatting
        if (isset($this->dateAliases)) {
            foreach ($this->dateAliases as $alias => $originalField) {
                $rawValue = $this->getRawOriginal($originalField);
                if ($rawValue !== null) {
                    $attributes[$alias] = $this->formatDateValue($rawValue, $originalField);
                }
            }
        }

        // Handle time aliases using raw original values to avoid double formatting
        if (isset($this->timeAliases)) {
            foreach ($this->timeAliases as $alias => $originalField) {
                $rawValue = $this->getRawOriginal($originalField);
                if ($rawValue !== null) {
                    $attributes[$alias] = $this->formatDateValue($rawValue, 'time');
                }
            }
        }

        return $attributes;
    }

    /**
     * Get the attributes that should be treated as dates.
     *
     * @return array
     */
    public function getDates()
    {
        $dates = isset($this->dates) ? $this->dates : [];
        return array_unique(array_merge($dates, [
            'created_at',
            'updated_at',
            'deleted_at'
        ]));
    }
}
