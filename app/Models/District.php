<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'city_id'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
