<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TechnicianSparePartRequestItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'technician_spare_part_request_id',
        'spare_part_id',
        'quantity',
        'approved_quantity',
        'item_no',
    ];

    public function request()
    {
        return $this->belongsTo(TechnicianSparePartRequest::class);
    }

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
