<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\DateFormatTrait;
use App\Traits\LogsActivity;

class Holiday extends Model {
    use HasFactory, DateFormatTrait,LogsActivity;

    protected $fillable = [
        'type',
        'class_ids',
        'date',
        'end_date',
        'title',
        'description',
        'school_id'
    ];

    protected $appends = ['default_date_format'];

    public function scopeOwner($query) {
        if(Auth::user()) {
            if (Auth::user()->school_id) {
                if (Auth::user()->hasRole('School Admin') || Auth::user()->hasRole('Teacher')) {
                    return $query->where('school_id', Auth::user()->school_id);
                }
    
                if (Auth::user()->hasRole('Student')) {
                    return $query->where('school_id', Auth::user()->school_id);
                }
                return $query->where('school_id', Auth::user()->school_id);
            }
    
            if (!Auth::user()->school_id) {
                if (Auth::user()->hasRole('Super Admin')) {
                    return $query;
                }
                return $query;
            }
        }

        return $query;
    }

    protected function setDateAttribute($value) {
        $this->attributes['date'] = date('Y-m-d', strtotime($value));
    }

    public function getDefaultDateFormatAttribute() {
        return date('d-m-Y', strtotime($this->date));
    }

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }

    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }

    public function getDateAttribute() {
        $date = $this->formatDateOnly($this->getRawOriginal('date'));
        $end_date = $this->getRawOriginal('end_date') ? $this->formatDateOnly($this->getRawOriginal('end_date')) : null;
        
        if ($end_date && $date !== $end_date) {
            return $date . ' to ' . $end_date;
        }
        
        return $date;
    }
   
}
