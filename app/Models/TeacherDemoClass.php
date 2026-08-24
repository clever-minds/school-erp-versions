<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherDemoClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'school_id',
        'subject',
        'class_name',
        'date',
        'time',
        'location',
        'instructions',
        'status',
        'overall_rating',
        'remarks'
    ];
}
