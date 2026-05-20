<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportForm extends Model
{
    protected $fillable = [
        'subject',
        'details',
        'user_type',
        'platform',
        'status',
        'notes',
        'user_id',
        'name',
        'phone',

    ];

    protected $casts = [
        'notes' => 'array',
];

    public function user()
    {
        if ($this->user_type === 'technician') {
            return $this->belongsTo(Technician::class, 'user_id');
        } elseif ($this->user_type === 'customer') {
            return $this->belongsTo(Customer::class, 'user_id');
        }

        return null;
    }
}
