<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchStream extends Model
{
    use HasFactory;

    protected $table = 'sch_streams';

    protected $fillable = [
        'name',
        'status',
    ];
}
