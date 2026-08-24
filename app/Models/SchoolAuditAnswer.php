<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolAuditAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['school_audit_id', 'audit_question_id', 'answer', 'remarks', 'image'];

    public function audit()
    {
        return $this->belongsTo(SchoolAudit::class, 'school_audit_id');
    }

    public function question()
    {
        return $this->belongsTo(AuditQuestion::class, 'audit_question_id');
    }
}
