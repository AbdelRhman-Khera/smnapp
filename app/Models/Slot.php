<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'date',
        'time',
        'is_booked',
    ];

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }


}
