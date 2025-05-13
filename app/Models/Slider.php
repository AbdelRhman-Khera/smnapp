<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory;

    protected $fillable = ['title_ar', 'title_en', 'image', 'link'];
    protected $appends = ['title', 'image_url'];
    // protected $hidden = ['title_ar', 'title_en', 'image'];

    public function getTitleAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function getImageUrlAttribute()
    {
        return url('storage/' . $this->image);
    }

}
