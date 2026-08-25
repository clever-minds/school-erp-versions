<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\SessionYearsTracking;
use App\Traits\DateFormatTrait;
use App\Services\CachingService;

class PayrollSetting extends Model
{
    use HasFactory, SoftDeletes, DateFormatTrait;

    protected $fillable = [
        'id',
        'name',
        'amount',
        'percentage',
        'type',
        'school_id',
        'session_year_id'
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


    public function staffSalary()
    {
        return $this->hasMany(StaffSalary::class, 'payroll_setting_id', 'id');
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
