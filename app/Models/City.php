<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class City extends Model
{
    use LogsActivity;
    protected $fillable = ['name_ar', 'name_en'];
    protected $appends = ['name'];
    // protected $hidden = ['name_ar', 'name_en','created_at', 'updated_at'];
    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function districts()
    {
        return $this->hasMany(District::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();

    }

}
