<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Traits\DateFormatTrait;

class School extends Model
{
    use SoftDeletes;
    use HasFactory;
    use DateFormatTrait;
    protected $fillable = [
        'name',
        'address',
        'support_phone',
        'support_email',
        'tagline',
        'logo',
        'admin_id',
        'status',
        'domain',
        'database_name',
        'code',
        'type',
        'domain_type',
        'installed',
        'progress',
        'provision_step',
    ];

    /** Provisioning steps in order with percentage milestones */
    public const STEPS = [
        'job_started'              => ['label' => 'Initializing...',          'progress' => 0],
        'database_created'         => ['label' => 'Creating Database...',     'progress' => 15],
        'migrations_completed'     => ['label' => 'Migrating Tables...',       'progress' => 40],
        'seeders_executed'         => ['label' => 'Configuring Defaults...',   'progress' => 65],
        'school_activated'         => ['label' => 'Activating School...',      'progress' => 85],
        'welcome_email_sent'       => ['label' => 'Sending Welcome Email...',  'progress' => 93],
        'verification_email_sent'  => ['label' => 'Sending Verification...',   'progress' => 98],
        'provisioning_completed'   => ['label' => 'Setup Complete',           'progress' => 100],
    ];

    protected $casts = [
        'progress' => 'array',
    ];

    protected $hidden = ['database_name'];
    protected $appends = ['original_created_at', 'original_updated_at'];
    //Getter Attributes
    public function getLogoAttribute($value)
    {
        return url(Storage::url($value));
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'admin_id')->withTrashed();
    }

    public function subscription()
    {
        return $this->hasMany(Subscription::class);
    }

    public function addon()
    {
        $today_date = Carbon::now()->format('Y-m-d');
        return $this->hasManyThrough(Feature::class, AddonSubscription::class, 'school_id', 'id', 'id', 'feature_id')
            ->where('start_date', '<=', $today_date)->where('end_date', '>=', $today_date);
    }

    public function features()
    {
        $today_date = Carbon::now()->format('Y-m-d');
        return $this->hasManyThrough(SubscriptionFeature::class, Subscription::class)->where('start_date', '<=', $today_date)->where('end_date', '>=', $today_date);
    }

    public function test()
    {
        return $this->features->merge($this->addon);
    }

    public function extra_school_details()
    {
        return $this->hasMany(ExtraSchoolData::class, 'school_id', 'id');
    }

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }

    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }
    public function getOriginalCreatedAtAttribute()
    {
        return $this->getRawOriginal('created_at');
    }

    public function getOriginalUpdatedAtAttribute()
    {
        return $this->getRawOriginal('updated_at');
    }

    public function school_board()
    {
        return $this->hasOne(SchoolBoard::class, 'school_id', 'id');
    }
}
