<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

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

    // public function toFcm($notifiable)
    // {
    //     return FcmMessage::create()
    //         ->data([
    //             'request_id' => $this->requestId,
    //         ])
    //         ->notification(
    //             FcmNotification::create()
    //                 ->title(__('notifications.technician.title'))
    //                 ->body($this->message)
    //                 ->sound('default')
    //         );
    // }

    public function toFcm($notifiable): FcmMessage
    {
        return new FcmMessage(
            notification: new FcmNotification(
                title: __('notifications.technician.title'),
                body: $this->message,
                // image: 'http://example.com/url-to-image-here.png'
            ),
            data: [
                'request_id' => $this->requestId,
            ],
            custom: [
                'android' => [
                    'notification' => [
                        'color' => '#0A0A0A',
                        'sound' => 'default',
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'analytics',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default'
                        ],
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'analytics',
                    ],
                ],
            ]
        );
    }
}
