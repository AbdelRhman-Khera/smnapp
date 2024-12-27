<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Landing extends Model
{
    protected $fillable = [
        'main_title_ar',
        'main_title_en',
        'main_description_ar',
        'main_description_en',
        'main_image',
        'logo',
        'feature_title_ar',
        'feature_title_en',
        'feature_description_ar',
        'feature_description_en',
        'feature_image',
        'steps',
        'services',
        'services_image',
        'store_title_ar',
        'store_title_en',
        'store_description_ar',
        'store_description_en',
        'store_image',
        'store_url',
        'map_image',
        'download_title_ar',
        'download_title_en',
        'app_store_url',
        'google_play_url',
        'download_image',
    ];

    protected $casts = [
        'steps' => 'array',
        'services' => 'array',
    ];
}
