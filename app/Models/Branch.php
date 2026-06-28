<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Branch extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name_ar',
        'name_en',
        'sap_id',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function deviceWithdrawalRequests()
    {
        return $this->hasMany(DeviceWithdrawalRequest::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
