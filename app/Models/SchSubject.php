<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SchSubject extends Model
{
    use HasFactory;

    protected $table = 'sch_subjects';

    protected $fillable = [
        'name',
        'code',
        'bg_color',
        'image',
        'type',
        'status',
    ];

    public function getImageAttribute($value)
    {
        return url(Storage::url($value));
    }
}
