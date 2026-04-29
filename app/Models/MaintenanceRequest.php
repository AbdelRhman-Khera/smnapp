<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MaintenanceRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'customer_id',
        'technician_id',
        'type',
        'products',
        'address_id',
        'sap_order_id',
        'problem_description',
        'invoice_number',
        'photos',
        'last_maintenance_date',
        'notes',
        'slot_id',
        'last_status',
        'entry_sap_id',
        'sap_sync_status',
        'sap_sales_order_no',
        'sap_last_error',
        'hours',
        'extra_slot_id',


    ];

    protected $casts = [
        'photos' => 'array',
        'notes' => 'array',
        'extra_slot_id' => 'array',
    ];

    protected $appends = ['current_status'];

    public function getPhotosAttribute($value)
    {
        $photos = json_decode($value, true);

        if (!is_array($photos)) {
            return [];
        }

        return array_filter(array_map(function ($path) {
            if (is_string($path)) {
                return url('storage/' . ltrim($path, '/'));
            }
            return null; // Ignore non-string elements
        }, $photos));
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class, 'technician_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function slot()
    {
        return $this->belongsTo(Slot::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'maintenance_request_product')
            ->withPivot(['quantity'])
            ->withTimestamps();
    }

    public function statuses()
    {
        return $this->hasMany(RequestStatus::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function getCurrentStatusAttribute()
    {
        return $this->statuses()->latest()->first();
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class);
    }

    public function maintenanceRequests()
    {
        return $this->hasManyThrough(
            \App\Models\MaintenanceRequest::class,
            \App\Models\Address::class,
            'district_id',
            'address_id',
            'id',
            'id'
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function sapLogs()
    {
        return $this->hasMany(\App\Models\SapRequestLog::class);
    }

    public function calculateHoursFromProducts(): float
    {
        $this->loadMissing('products');

        return $this->products->sum(function ($product) {
            $quantity = (float) ($product->pivot->quantity ?? 1);
            $productHours = (float) ($product->hours ?? 0);

            return $productHours * $quantity;
        });
    }

    public function recalculateHours(): void
    {
        $this->updateQuietly([
            'hours' => $this->calculateHoursFromProducts(),
        ]);
    }
}
