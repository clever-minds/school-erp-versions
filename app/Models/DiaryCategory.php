<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\DateFormatTrait;
use App\Traits\LogsActivity;

class DiaryCategory extends Model
{
    use HasFactory, SoftDeletes, DateFormatTrait,LogsActivity;

    protected $guarded = [];

    public function diary()
    {
        return $this->hasMany(Diary::class);
        
    }

    public function getCreatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('created_at'));
    }

    public function getUpdatedAtAttribute()
    {
        return $this->formatDateValue($this->getRawOriginal('updated_at'));
    }
}
