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

    protected $appends = [
        'main_title',
        'main_description',
        'feature_title',
        'feature_description',
        'store_title',
        'store_description',
        'download_title',
        'translated_steps',
        'translated_services',
    ];

    protected $hidden = [
        'main_title_ar',
        'main_title_en',
        'main_description_ar',
        'main_description_en',
        'feature_title_ar',
        'feature_title_en',
        'feature_description_ar',
        'feature_description_en',
        'store_title_ar',
        'store_title_en',
        'store_description_ar',
        'store_description_en',
        'download_title_ar',
        'download_title_en',
        'steps',
        'services',
    ];

    public function getMainTitleAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->main_title_ar : $this->main_title_en;
    }

    public function getMainDescriptionAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->main_description_ar : $this->main_description_en;
    }

    public function getFeatureTitleAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->feature_title_ar : $this->feature_title_en;
    }

    public function getFeatureDescriptionAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->feature_description_ar : $this->feature_description_en;
    }

    public function getStoreTitleAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->store_title_ar : $this->store_title_en;
    }

    public function getStoreDescriptionAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->store_description_ar : $this->store_description_en;
    }

    public function getDownloadTitleAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->download_title_ar : $this->download_title_en;
    }

    public function getTranslatedStepsAttribute()
    {
        $locale = app()->getLocale();
        return array_map(function ($step) use ($locale) {
            return [
                'step_icon' => $step['step_icon'],
                'step_title' => $locale === 'ar' ? $step['step_title_ar'] : $step['step_title_en'],
                'step_description' => $locale === 'ar' ? $step['step_description_ar'] : $step['step_description_en'],
            ];
        }, $this->steps ?? []);
    }

    public function getTranslatedServicesAttribute()
    {
        $locale = app()->getLocale();
        return array_map(function ($service) use ($locale) {
            return [
                'service_title' => $locale === 'ar' ? $service['service_title_ar'] : $service['service_title_en'],
                'service_description' => $locale === 'ar' ? $service['service_description_ar'] : $service['service_description_en'],
            ];
        }, $this->services ?? []);
    }
}
