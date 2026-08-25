<?php

namespace App\Models;

use App\Services\CachingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Traits\DateFormatTrait;


class Announcement extends Model {
    protected $fillable = [
        'title',
        'description',
        'table_type',
        'table_id',
        'session_year_id',
        'school_id',
    ];

    use DateFormatTrait;

    public function file() {
        return $this->morphMany(File::class, 'modal');
    }

    public function scopeOwner($query) {
        if (Auth::user()) {
            $sessionYearId = app(CachingService::class)->getSessionYear()->id;
            if (Auth::user()->hasRole('School Admin')) {
                return $query->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
            }
    
            if(Auth::user()->hasRole('Teacher')) {
                $teacherId = Auth::user()->id;
                return $query->whereHas('announcement_class.subject_teacher', function ($query) use ($teacherId, $sessionYearId) {
                    $query->where(['teacher_id' => $teacherId, 'session_year_id' => $sessionYearId])
                        ->whereColumn('class_section_id', 'announcement_classes.class_section_id');
                })->orWhereHas('announcement_class',function($q) {
                    $q->where('class_subject_id',null);
                })->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
            }
    
            if (Auth::user()->hasRole('Student')) {
                return $query->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
            }
    
            if (Auth::user()->school_id) {
                return $query->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
            }
    
            if (!Auth::user()->school_id) {
                if (Auth::user()->hasRole('Super Admin')) {
                    return $query;
                }
                return $query;
            }
        }
        return $query;
    }

    protected static function boot() {
        parent::boot();
        static::deleting(static function ($announcement) { // before delete() method call this
            if ($announcement->file) {
                foreach ($announcement->file as $file) {
                    if (Storage::disk('public')->exists($file->getRawOriginal('file_url'))) {
                        Storage::disk('public')->delete($file->getRawOriginal('file_url'));
                    }
                }

                $announcement->file()->delete();
            }
        });
    }

    public function announcement_class() {
        return $this->hasMany(AnnouncementClass::class);
    }

    public function scopeSubjectTeacher($query) {
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $teacherId = Auth::user()->id;
            $sessionYearId = app(CachingService::class)->getSessionYear()->id;
            return $query->whereHas('announcement_class.subject_teacher', function ($query) use ($teacherId, $sessionYearId) {
                $query->where(['teacher_id' => $teacherId, 'session_year_id' => $sessionYearId])
                    ->whereColumn('class_section_id', 'announcement_classes.class_section_id');
            })->orWhereHas('announcement_class',function($q) {
                $q->where('class_subject_id',null);
            });
            return $query->where('school_id', Auth::user()->school_id);
        }
        return $query;
    }

    public function roles() {
        return $this->hasMany(Role::class, 'id', 'id');
    }

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }
    
    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }
}
