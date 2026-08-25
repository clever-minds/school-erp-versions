<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\DateFormatTrait;
use App\Services\CachingService;

class StaffSalary extends Model
{
    use HasFactory, DateFormatTrait;

    protected $fillable = [
        'id',
        'staff_id',
        'payroll_setting_id',
        'amount',
        'percentage',
        'expiry_date',
        'session_year_id',
        'school_id',
    ];

    public function session_year()
    {
        return $this->belongsTo(SessionYear::class);
    }
    public function scopeOwner($query)
    {
        if (Auth::user()) {
            $sessionYearId = app(CachingService::class)->getSessionYear()->id;
            return $query->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
        }
        return $query;
    }

    public function payrollSetting()
    {
        return $this->belongsTo(PayrollSetting::class)->withTrashed();
    }

    /**
     * Get the staff that owns the StaffSalary
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }
    
    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }
}
