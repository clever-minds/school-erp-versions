<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Fee extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'name',
        'due_date',
        'due_charges',
        'include_fee_installments',
        'school_id',
        'session_year_id',
        'created_at',
        'updated_at'
    ];
    protected $appends = ['compulsory_fees','optional_fees'];

    public function scopeOwner($query)
    {
        if (Auth::user()->hasRole('Super Admin')) {
            return $query;
        }

        if (Auth::user()->hasRole('School Admin') || Auth::user()->hasRole('Teacher')) {
            return $query->where('school_id', Auth::user()->school_id);
        }

        if (Auth::user()->hasRole('Student')) {
            return $query->where('school_id', Auth::user()->school_id);
        }

        return $query;
    }

    public function installments() {
        return $this->hasMany(InstallmentFee::class, 'fees_id')->withTrashed();
    }

    public function fees_class() {
        return $this->hasMany(FeesClass::class, 'fees_id')->withTrashed();
    }

    public function getCompulsoryFeesAttribute() {
        if ($this->relationLoaded('fees_class')) {
            return $this->fees_class->where('choiceable',0);
        }
        return null;
    }

    public function getOptionalFeesAttribute() {
        if ($this->relationLoaded('fees_class')) {
            return $this->fees_class->where('choiceable',1);
        }
        return null;
    }

    public function fees_paid(){
        return $this->hasMany(FeesPaid::class, 'fees_id')->withTrashed();
    }


}
