<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name_ar', 'name_en'];
    protected $appends = ['name'];
    protected $hidden = ['name_ar', 'name_en'];
    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function districts()
    {
        return $this->hasMany(District::class);
    }

}
