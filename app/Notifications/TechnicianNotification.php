<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\FcmChannel;

class TechnicianNotification extends Notification
{
    protected $message;
    protected $requestId;

    public function __construct($message, $requestId)
    {
        $this->message = $message;
        $this->requestId = $requestId;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast', FcmChannel::class];
    }

    public function toArray($notifiable)
    {
        return [
            'request_id' => $this->requestId,
            'message' => $this->message
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'request_id' => $this->requestId,
            'message' => $this->message
        ]);
    }

    public function toFcm($notifiable)
    {
        return FcmMessage::create()
            ->setData([
                'request_id' => $this->requestId
            ])
            ->setNotification([
                'title' => __('notifications.technician.title'),
                'body' => $this->message,
                'sound' => 'default',
            ]);
    }
}
