<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TechnicianEarning extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'technician_id',
        'maintenance_request_id',
        'request_type',
        'amount',
        'status',
        'payout_request_id',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function payoutRequest()
    {
        return $this->belongsTo(TechnicianPayoutRequest::class, 'payout_request_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
