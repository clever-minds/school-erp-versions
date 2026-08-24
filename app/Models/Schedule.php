<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'description', 'date', 'school_id'];

    public function scopeOwner($query)
    {
        return $query->where('school_id', auth()->user()->school_id);
    }
}
