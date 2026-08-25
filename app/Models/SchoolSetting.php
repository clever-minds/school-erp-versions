<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SchoolSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'data',
        'type',
        'school_id'
    ];

    public $timestamps = false;

    public function getDataAttribute($value) {
        if ($this->attributes['type'] == 'file') {
            if ($value) {
                return url(Storage::url($value));
            }
            return '';
        }

        return $value;
    }

    public function scopeOwner($query)
    {
        if (Auth::user()->hasRole('Super Admin')) {
            return $query;
        }

        if (Auth::user()->hasRole('School Admin') || Auth::user()->hasRole('Teacher')) {
            return $query->where('school_id', Auth::user()->school_id);
        }

        if (Auth::user()->hasRole('Student')) {
            return $query->where('school_id', Auth::user()->school_id);
        }

        return $query;
    }
}
