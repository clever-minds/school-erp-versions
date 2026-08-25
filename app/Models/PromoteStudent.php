<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\DateFormatTrait;
use App\Services\CachingService;


class PromoteStudent extends Model
{
    use HasFactory, DateFormatTrait;
    protected $fillable = [
        'student_id',
        'class_section_id',
        'session_year_id',
        'result',
        'status',
        'school_id',
        'current_class_section_id',
        'current_session_year_id',
        'roll_number'
    ];

    public function student()
    {
        return $this->belongsTo(Students::class)->withTrashed();
    }

    public function scopeOwner($query)
    {
        if (Auth::user()) {
            if (Auth::user()->hasRole('Super Admin')) {
                return $query;
            }

            if (Auth::user()->hasRole('School Admin')) {
                $sessionYearId = app(CachingService::class)->getSessionYear()->id;
                return $query->where($this->getTable() . '.school_id', Auth::user()->school_id)->where($this->getTable() . '.session_year_id', $sessionYearId);
            }

            if (Auth::user()->hasRole('Student')) {
                $sessionYearId = app(CachingService::class)->getSessionYear()->id;
                return $query->where($this->getTable() . '.school_id', Auth::user()->school_id)->where($this->getTable() . '.session_year_id', $sessionYearId);
            }
        }

        return $query;
    }

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }

    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class)->withTrashed();
    }

    public function current_class_section()
    {
        return $this->belongsTo(ClassSection::class, 'current_class_section_id')->withTrashed();
    }

    public function session_year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }
}
