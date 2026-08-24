<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\DateFormatTrait;
use Illuminate\Support\Facades\Auth;

class StaffAttendance extends Model
{
    use HasFactory, DateFormatTrait;

    protected $fillable = [
        'user_id',
        'school_id',
        'date',
        'check_in',
        'check_out',
        'check_in_location',
        'check_out_location',
        'latitude',
        'longitude',
        'check_out_latitude',
        'check_out_longitude',
        'check_in_ip',
        'check_out_ip',
        'status',
        'type',
        'scanned_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function scopeOwner($query)
    {
        if (Auth::user() && Auth::user()->school_id) {
            return $query->where('school_id', Auth::user()->school_id);
        }
        return $query;
    }
}
