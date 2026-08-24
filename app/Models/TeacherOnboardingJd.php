<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherOnboardingJd extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'description', 'school_id'];
}
