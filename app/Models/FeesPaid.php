<?php

namespace App\Models;

use App\Repositories\FeesClass\FeesClassInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;


class FeesPaid extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'fees_id',
        'student_id',
        'class_id',
        'is_fully_paid',
        'amount',
        'date',
        'school_id',
        'session_year_id'
    ];

    protected $appends = ['compulsory_data'];

    public function session_year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id')->withTrashed();
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id')->withTrashed();
    }

    public function class()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id')->with('medium')->withTrashed();
    }

    public function fees()
    {
        return $this->belongsTo(Fee::class, 'fees_id')->withTrashed();
    }

    public function optional_fee()
    {
        return $this->hasMany(OptionalFee::class, 'fees_paid_id')->withTrashed();
    }

    public function compulsory_fee()
    {
        return $this->hasMany(CompulsoryFee::class, 'fees_paid_id')->withTrashed();
    }

    public function getCompulsoryDataAttribute() {
        if ($this->relationLoaded('compulsory_fee')) {
            if($this->compulsory_fee->where('type',"1")){
                $feesClass = app(FeesClassInterface::class);
                return $feesClass->builder()->where(['fees_id' => $this->fees_id, 'class_id' => $this->class_id, 'choiceable' => "0"])->with('fees_type')->get();
            }
        }
        return null;
    }

    public function scopeOwner($query)
    {
        if(Auth::user()){
            if (Auth::user()->hasRole('Super Admin')) {
                return $query;
            }

            if (Auth::user()->hasRole('School Admin') || Auth::user()->hasRole('Teacher')) {
                return $query->where('school_id', Auth::user()->school_id);
            }

            if (Auth::user()->hasRole('Student')) {
                return $query->where('school_id', Auth::user()->school_id);
            }
        }

        return $query;
    }

}
