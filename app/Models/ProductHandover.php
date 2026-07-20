<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductHandover extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'maintenance_request_id',
        'technician_id',
        'status',
        'notes',
        'technician_notes',
        'created_by',
        'processed_at',
        'canceled_by',
        'canceled_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function items()
    {
        return $this->hasMany(ProductHandoverItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceledBy()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    public function accept(?string $technicianNotes = null): void
    {
        $this->update([
            'status' => 'accepted',
            'technician_notes' => $technicianNotes,
            'processed_at' => now(),
        ]);

        $this->maintenanceRequest?->update([
            'technician_received_products' => true,
        ]);
    }

    public function reject(?string $technicianNotes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'technician_notes' => $technicianNotes,
            'processed_at' => now(),
        ]);
    }

    public function cancel(?int $canceledById = null): void
    {
        $this->update([
            'status' => 'canceled',
            'canceled_by' => $canceledById,
            'canceled_at' => now(),
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
