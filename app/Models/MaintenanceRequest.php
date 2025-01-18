<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRequest extends Model
{
    use HasFactory, SoftDeletes;

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


    ];

    protected $casts = [
        'photos' => 'array',
        'notes' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
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
        return $this->belongsToMany(Product::class, 'maintenance_request_product');
    }

    public function statuses()
    {
        return $this->hasMany(RequestStatus::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}