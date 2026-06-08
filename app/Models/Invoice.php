<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'maintenance_request_id',
        'total',
        'service_cost',
        'payment_method',
        'status',
        'payment_details',
        'qr_code',
        'remittance',
        'machine_pic',
    ];

    protected $casts = [
        'payment_details' => 'array',
    ];

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'invoice_product')->withPivot('quantity');
    }

    public function spareParts()
    {
        return $this->belongsToMany(SparePart::class, 'invoice_spare_part')->withPivot('quantity', 'price');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'invoice_service');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
