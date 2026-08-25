<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchClass extends Model
{
    use HasFactory;

    protected $table = 'sch_classes';

    protected $fillable = [
        'name',
        'status',
        'sort_order',
    ];
}
