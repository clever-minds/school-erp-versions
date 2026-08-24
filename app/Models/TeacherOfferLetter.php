<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherOfferLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'designation',
        'department',
        'salary',
        'joining_date',
        'reporting_time',
        'job_location',
        'token',
        'token_expires_at',
        'status',
    ];
}
