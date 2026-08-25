<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchSection extends Model
{
    use HasFactory;

    protected $table = 'sch_sections';

    protected $fillable = [
        'name',
        'status',
        'sort_order',
    ];
}
