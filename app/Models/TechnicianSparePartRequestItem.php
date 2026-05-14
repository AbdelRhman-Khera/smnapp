<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianSparePartRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_spare_part_request_id',
        'spare_part_id',
        'quantity',
        'approved_quantity',
    ];

    public function request()
    {
        return $this->belongsTo(TechnicianSparePartRequest::class);
    }

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }
}
