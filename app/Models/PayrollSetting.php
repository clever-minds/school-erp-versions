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
        'school_id'
    ];

    public function scopeOwner($query)
    {
        if (Auth::user()) {
            return $query->where(['school_id' => Auth::user()->school_id]);
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
