<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Slot extends Model
{
    use HasFactory, LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();

    }

}
