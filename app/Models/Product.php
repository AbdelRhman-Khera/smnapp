<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use HasFactory , LogsActivity;
    protected $fillable = ['sap_id', 'name_ar', 'name_en', 'description_ar', 'description_en', 'image', 'category_id'];
    protected $appends = ['name', 'description', 'category_name', 'image_url'];
    // protected $hidden = ['name_ar', 'name_en', 'image', 'description_ar', 'description_en', 'category_id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function maintenanceRequests()
    {
        return $this->belongsToMany(MaintenanceRequest::class, 'maintenance_request_product');
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_product')->withPivot('quantity');
    }

    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }
    public function getDescriptionAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->description_ar : $this->description_en;
    }
    public function getCategoryNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->category->name_ar : $this->category->name_en;
    }

    public function getImageUrlAttribute()
    {
        return url('storage/' . $this->image);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();

    }
}
