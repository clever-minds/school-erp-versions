<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'class_section_id'
    ];

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class);
    }
}
