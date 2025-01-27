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

    ];
}
