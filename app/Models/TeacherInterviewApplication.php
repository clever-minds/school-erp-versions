<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherInterviewApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'phone',
        'resume_path',
        'status',
        'remarks',
        'document_verification_date',
        'document_verification_time'
    ];

    public function interview()
    {
        return $this->hasOne(TeacherInterview::class, 'application_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function demoClass()
    {
        return $this->hasOne(TeacherDemoClass::class, 'application_id');
    }

    public function joiningDocuments()
    {
        return $this->hasMany(TeacherJoiningDocument::class, 'application_id');
    }
}
