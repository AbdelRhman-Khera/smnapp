<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Area extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'maintenance_fee',
        'extra_hours',
        'is_active',
    ];

    protected $casts = [
        'maintenance_fee' => 'float',
        'extra_hours' => 'float',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'name',
        'description',
    ];

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function getNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getDescriptionAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->description_ar : $this->description_en;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
