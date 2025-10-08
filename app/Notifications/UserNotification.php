<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class UserNotification extends Notification
{
    use Queueable;
    protected $message;
    protected $requestId;

    public function __construct($message, $requestId)
    {
        $this->message = $message;
        $this->requestId = $requestId;
    }

    public function via($notifiable)
    {
        // return ['database', FcmChannel::class];
        return ['database'];
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
    //             'request_id' => (string) $this->requestId,
    //             'message' => (string) $this->message
    //         ])
    //         ->notification(FcmNotification::create()
    //             ->title(__('notifications.technician.title'))
    //             ->body($this->message)
    //         );
    // }


}
