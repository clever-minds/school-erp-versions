<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;
use App\Traits\DateFormatTrait;
use App\Traits\LogsActivity;

class Notification extends Model
{
    use HasFactory, DateFormatTrait,LogsActivity;
    protected $fillable = ['title','message','image','send_to','session_year_id','school_id','event_date', 'sender_id'];

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

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function notificationClasses()
    {
        return $this->hasMany(NotificationClass::class);
    }

    public function notificationUsers()
    {
        return $this->hasMany(NotificationUser::class);
    }

    public function scopeOwner()
    {
        if (Auth::user()) {
            if (Auth::user()->school_id) {
                return $this->where('school_id',Auth::user()->school_id);
            }
            return $this;
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

    public function session_years_trackings()
    {
        return $this->hasMany(SessionYearsTracking::class, 'modal_id', 'id')->where('modal_type', 'App\Models\Notification');
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
