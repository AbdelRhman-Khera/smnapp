<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductHandoverItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'product_handover_id',
        'product_id',
        'serial_number',
    ];

    public function handover()
    {
        return $this->belongsTo(ProductHandover::class, 'product_handover_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
