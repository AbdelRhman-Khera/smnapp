<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class District extends Model
{
    use LogsActivity;
    protected $fillable = ['name_ar', 'name_en', 'city_id', 'area_id', 'available_days', 'is_active', 'hourly_rate'];
    protected $appends = ['name', 'city_name', 'area_name'];
    protected $casts = [
        'available_days' => 'array',
    ];
    // protected $hidden = ['name_ar', 'name_en', 'city_id','created_at', 'updated_at'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getCityNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->city?->name_ar : $this->city?->name_en;
    }

    public function getAreaNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->area?->name_ar : $this->area?->name_en;
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();

    }
}
