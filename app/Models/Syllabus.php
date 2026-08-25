<?php

namespace App\Models;

use App\Traits\DateFormatTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Syllabus extends Model
{
    use HasFactory, DateFormatTrait;
    protected $connection = 'school';
    protected $table = 'syllabus';

    protected $fillable = [
        'class_id',
        'subject_id',
        'title',
        'status',
    ];

    public function class()
    {
        return $this->belongsTo(ClassSchool::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get all of the class_subject for the Syllabus
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function class_subject()
    {
        return $this->hasMany(ClassSubject::class, 'syllabus_id', 'id');
    }

    /**
     * Get all of the lesson_common for the Syllabus
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lesson_common()
    {
        return $this->hasMany(LessonCommon::class, 'syllabus_id', 'id');
    }

    public function getCreatedAtAttribute($value)
    {
        return $this->formatDateValue($value);
    }

    public function getUpdatedAtAttribute($value)
    {
        return $this->formatDateValue($value);
    }
}
