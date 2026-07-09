<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PushNotificationCampaign extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'audience',
        'recipient_scope',
        'recipient_ids',
        'title_ar',
        'title_en',
        'body_ar',
        'body_en',
        'deep_link',
        'send_count',
        'last_targeted_count',
        'last_success_count',
        'last_failed_count',
        'last_sent_at',
        'created_by',
    ];

    protected $casts = [
        'recipient_ids' => 'array',
        'last_sent_at' => 'datetime',
    ];

    public function sends()
    {
        return $this->hasMany(PushNotificationSend::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
