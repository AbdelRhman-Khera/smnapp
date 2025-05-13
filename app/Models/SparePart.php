<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SparePart extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'price',
        'stock',
        'image',
        'sap_id',
    ];

    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
    ];

    // protected $hidden = [
    //     'name_ar',
    //     'name_en',
    //     'description_ar',
    //     'description_en',
    //     'image',
    //     'created_at',
    //     'updated_at',
    // ];

    protected $appends = [
        'image_url',
        'name',
        'description',
    ];

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

    public function getImageUrlAttribute()
    {
        return url('storage/' . $this->image);
    }

}
