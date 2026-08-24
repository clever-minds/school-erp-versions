<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'audit_category_id', 'type', 'audit_option_group_id', 'status', 'category'];

    public function optionGroup()
    {
        return $this->belongsTo(AuditOptionGroup::class, 'audit_option_group_id');
    }

    public function category()
    {
        return $this->belongsTo(AuditCategory::class, 'audit_category_id');
    }

    public function answers()
    {
        return $this->hasMany(SchoolAuditAnswer::class);
    }
}
