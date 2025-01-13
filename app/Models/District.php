<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'city_id'];
    protected $appends = ['name', 'city_name'];
    protected $hidden = ['name_ar', 'name_en', 'city_id','created_at', 'updated_at'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getCityNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->city->name_ar : $this->city->name_en;
    }
}
