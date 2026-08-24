<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditOptionGroup extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'option_values'];

    protected $casts = [
        'option_values' => 'array',
    ];

    public function questions()
    {
        return $this->hasMany(AuditQuestion::class, 'audit_option_group_id');
    }
}
