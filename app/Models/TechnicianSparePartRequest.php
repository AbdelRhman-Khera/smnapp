<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianSparePartRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'technician_id',
        'notes',
        'status',
        'sap_ref',
        'response',
        'delivered_at',
        'gr_response',
        'gr_sent_at',
    ];

    protected $casts = [
        'response' => 'array',
        'gr_response' => 'array',
        'delivered_at' => 'datetime',
        'gr_sent_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function items()
    {
        return $this->hasMany(TechnicianSparePartRequestItem::class);
    }
}
