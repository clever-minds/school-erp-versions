<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\ManageExpense;

class ExpanceTransLog extends Model
{
    use HasFactory;

    protected $table = 'expance_trans_log';

    protected $fillable = [
        'expnace_trans_id',
        'user_id',
        'trans_type',
        'amount',
        'description',
    ];

    /**
     * Relationship: Each log belongs to a ManageExpense
     */
    public function expense()
    {
        return $this->belongsTo(ManageExpense::class, 'expnace_trans_id', 'id');
    }

    /**
     * Relationship: Each log belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
