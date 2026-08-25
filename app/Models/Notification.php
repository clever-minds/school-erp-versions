<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;
use App\Traits\DateFormatTrait;
use App\Services\CachingService;

class Notification extends Model
{
    use HasFactory, DateFormatTrait;
    protected $fillable = ['title','message','image','send_to', 'is_custom', 'session_year_id','school_id'];
    protected $appends = ['type'];

    protected static function boot() {
        parent::boot();
        static::deleting(static function ($notification) { // before delete() method call this
            if ($notification->image) {
                if (Storage::disk('public')->exists($notification->getRawOriginal('image'))) {
                    Storage::disk('public')->delete($notification->getRawOriginal('image'));
                }
            }
        });
    }

    public function scopeOwner($query)
    {
        if (Auth::user()) {
            if (Auth::user()->school_id) {
                $sessionYearId = app(CachingService::class)->getSessionYear()->id;
                return $query->where(['school_id' => Auth::user()->school_id, 'session_year_id' => $sessionYearId]);
            }
            return $query;
        }
        return $this;
    }

    public function getImageAttribute($value)
    {
        if ($value) {
            return url(Storage::url($value));
        }
        return null;
    }

    public function user_notifications()
    {
        return $this->hasMany(UserNotification::class, 'notification_id', 'id');
    }

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }   
    
    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }
    
    public function getTypeAttribute()
    {
        return $this->is_custom == 1 ? 'custom' : 'system';
    }
}
