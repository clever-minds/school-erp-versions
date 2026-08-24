<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'status'];

    public function questions()
    {
        return $this->hasMany(AuditQuestion::class, 'audit_category_id');
    }

    public function audits()
    {
        return $this->belongsToMany(SchoolAudit::class, 'school_audit_category');
    }
}
