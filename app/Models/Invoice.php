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
        'invoice_type',
        'total',
        'service_cost',
        'payment_method',
        'status',
        'payment_details',
        'notes',
        'qr_code',
        'sap_sync_status',
        'sap_sales_order_no',
        'sap_last_error',
        'remittance',
        'machine_pic',
    ];

    protected $casts = [
        'payment_details' => 'array',
        'notes' => 'array',
        'total' => 'float',
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
