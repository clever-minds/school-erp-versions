<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Services\CachingService;
use App\Traits\DateFormatTrait;
use Carbon\Carbon;

class TransportationPayment extends Model
{
    use HasFactory, DateFormatTrait;

    protected $fillable = [
        'pickup_point_id',
        'user_id',
        'payment_transaction_id',
        'transportation_fee_id',
        'amount',
        'status',
        'paid_at',
        'session_year_id',
        'route_vehicle_id',
        'expiry_date',
        'shift_id',
        'include_amount',
        'included_amount',
    ];

    protected $appends = [
        'paid_at_date_only',
        'plan_status'
    ];

    public function scopeOwner($query)
    {
        if (Auth::user()) {
            $sessionYearId = app(CachingService::class)->getSessionYear()->id;
            return $query->where('session_year_id', $sessionYearId);
        }
        return $query;
    }

    /**
     * Pickup point relation.
     */
    public function pickupPoint()
    {
        return $this->belongsTo(PickupPoint::class);
    }

    /**
     * User (student/parent) relation.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Payment transaction relation.
     */
    public function paymentTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    /**
     * Transportation fee relation.
     */
    public function transportationFee()
    {
        return $this->belongsTo(TransportationFee::class);
    }

    /**
     * Session year relation.
     */
    public function sessionYear()
    {
        return $this->belongsTo(SessionYear::class);
    }

    public function routeVehicle()
    {
        return $this->belongsTo(RouteVehicle::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function getPaidAtAttribute($value)
    {
        return $this->formatDateValue($value);
    }

    public function getExpiryDateAttribute($value)
    {
        return $this->formatDateOnly($value);
    }

    public function getPaidAtDateOnlyAttribute()
    {
        return $this->formatDateOnly($this->getRawOriginal('paid_at'));
    }

    public function getPlanStatusAttribute()
    {
        $today = Carbon::now();

        if ($this->status == 'paid' && $today->between($this->getRawOriginal('paid_at'), $this->getRawOriginal('expiry_date'))) {
            return 'active';
        } else {
            return 'inactive';
        }
    }
}
