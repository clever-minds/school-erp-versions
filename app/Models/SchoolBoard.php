<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolBoard extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'sch_board_id',
    ];

    protected $connection = 'mysql';

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function schBoard()
    {
        return $this->belongsTo(SchBoard::class);
    }
}
