<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SapRequestLog extends Model
{
    use LogsActivity;

    protected $fillable = [
        'maintenance_request_id',
        'invoice_id',
        'action',
        'payment_method',
        'http_method',
        'url',
        'http_status',
        'sap_status',
        'sap_desc',
        'request_payload',
        'response_body',
        'error_message',
        'is_success',
        'created_by',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_body' => 'array',
        'is_success' => 'boolean',
    ];

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
