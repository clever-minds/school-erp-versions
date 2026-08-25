<?php

namespace App\Rules;

use App\Services\CachingService;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class SessionYearDate implements Rule
{
    protected $currentSessionYear;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->currentSessionYear = app(CachingService::class)->getSessionYear();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!$value) {
            return true;
        }

        try {
            $date = Carbon::parse($value)->format('Y-m-d');
            $start = $this->currentSessionYear->getRawOriginal('start_date');
            $end = $this->currentSessionYear->getRawOriginal('end_date');

            return ($date >= $start && $date <= $end);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be within the active session year (' . $this->currentSessionYear->name . ').';
    }
}
