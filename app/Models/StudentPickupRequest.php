<?php

namespace App\Models;

use App\Traits\DateFormatTrait;
use Illuminate\Database\Eloquent\Model;

class StudentPickupRequest extends Model
{
    use DateFormatTrait;

    protected $fillable = [
        'student_id',
        'parent_id',
        'pickup_person_name',
        'otp',
        'status',
        'verified_by',
        'verified_at',
        'school_id'
    ];

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id')->withTrashed();
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id')->withTrashed();
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by')->withTrashed();
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
