<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonTopicClass extends Model
{
    use HasFactory;
    protected $fillable = [
        'lesson_topic_id',
        'class_section_id',
        'school_id'
    ];

    protected $connection = 'school';

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id', 'id');
    }
}
