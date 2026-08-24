<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class SchoolPolicy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'file_url',
        'school_id'
    ];

    public function getFileUrlAttribute($value)
    {
        if ($value) {
            return url(Storage::url($value));
        }
        return null;
    }
}
