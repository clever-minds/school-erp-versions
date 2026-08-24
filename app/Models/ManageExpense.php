<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class ManageExpense extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'manage_expenses';

    protected $fillable = [
        'exp_id',
        'title',
        'description',
        'amount',
        'balance',
        'type',
        'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $incrementing = true; 

    protected $keyType = 'int'; 
    public function expense()
    {
        return $this->belongsTo(Expense::class, 'exp_id');
    }
}
