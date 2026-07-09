<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceWithdrawalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_withdrawal_request_id',
        'product_id',
        'serial_number',
        'notes',
        'photos',
        'status',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    public function getPhotosAttribute($value): array
    {
        $photos = json_decode($value, true);

        if (! is_array($photos)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($path) {
            return is_string($path) ? url('storage/' . ltrim($path, '/')) : null;
        }, $photos)));
    }

    public function withdrawalRequest()
    {
        return $this->belongsTo(DeviceWithdrawalRequest::class, 'device_withdrawal_request_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
