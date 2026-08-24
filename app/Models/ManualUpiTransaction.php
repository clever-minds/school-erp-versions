<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualUpiTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'amount',
        'transaction_id',
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(Students::class);
    }
}
