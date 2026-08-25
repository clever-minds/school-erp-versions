<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchBoard extends Model
{
    use HasFactory;

    protected $table = 'sch_boards';

    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    protected $connection = 'mysql';

    public function school_board()
    {
        return $this->hasOne(SchoolBoard::class);
    }
}
