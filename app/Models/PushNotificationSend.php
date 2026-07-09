<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotificationSend extends Model
{
    use HasFactory;

    protected $fillable = [
        'push_notification_campaign_id',
        'recipient_type',
        'recipient_id',
        'locale',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(PushNotificationCampaign::class, 'push_notification_campaign_id');
    }
}
