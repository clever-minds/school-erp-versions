<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchMedium extends Model
{
    use HasFactory;

    protected $table = 'sch_mediums';

    protected $fillable = [
        'name',
        'status',
    ];
}
