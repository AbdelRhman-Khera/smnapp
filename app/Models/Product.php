<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['sap_id', 'name_ar', 'name_en', 'description_ar', 'description_en', 'image', 'category_id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
