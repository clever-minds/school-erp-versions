<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\DateFormatTrait;

class CertificateAssignment extends Model
{
    use HasFactory, DateFormatTrait;

    protected $fillable = [
        'certificate_template_id',
        'user_id',
        'user_type',
        'class_section_id',
        'session_year_id',
        'exam_id',
        'roll_no',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
    ];

    public function certificate_template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function class_section(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function session_year(): BelongsTo
    {
        return $this->belongsTo(SessionYear::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function getIssuedAtAttribute($value)
    {
        return $this->formatDateOnly($value);
    }
}
