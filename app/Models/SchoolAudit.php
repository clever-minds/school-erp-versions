<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'auditor_id', 'audit_date', 'audit_type', 'remarks', 'status', 
        'name', 'frequency', 'due_date', 'submission_date', 'percentage_score', 'archived_at'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function answers()
    {
        return $this->hasMany(SchoolAuditAnswer::class);
    }

    public function categories()
    {
        return $this->belongsToMany(AuditCategory::class, 'school_audit_category');
    }
}
