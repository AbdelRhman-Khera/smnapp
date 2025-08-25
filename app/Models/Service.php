<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Service extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name_ar', 'name_en', 'description_ar', 'description_en', 'image', 'price', 'is_active'];
    protected $appends = ['name', 'description', 'image_url'];
    // protected $hidden = ['name_ar', 'name_en', 'description_ar', 'description_en'];

    public function getNameAttribute()
    {
        return app()->getLocale() == 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getDescriptionAttribute()
    {
        return app()->getLocale() == 'ar' ? $this->description_ar : $this->description_en;
    }

    public function getImageUrlAttribute()
    {
        return url('storage/' . $this->image);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_service');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();

    }


}
