<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'description', 'date', 'school_id', 'class_section_id'];
    
    public function class_section()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }

    public function scopeOwner($query)
    {
        return $query->where('school_id', auth()->user()->school_id);
    }
}
