<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','reason','from_date','to_date','status','school_id','session_year_id'];


    public function scopeOwner()
    {
        return $this->where('school_id', Auth::user()->school_id);
    }

    /**
     * Get the user that owns the Leave
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
