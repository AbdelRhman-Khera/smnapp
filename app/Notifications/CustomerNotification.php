<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class CustomerNotification extends Notification
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
        return ['database', FcmChannel::class,];
    }

    public function toArray($notifiable)
    {
        return [
            'request_id' => $this->requestId,
            'message' => $this->message
        ];
    }

    // public function toFcm($notifiable)
    // {
    //     try {
    //         $factory = (new Factory)->withServiceAccount(storage_path('app/firebase/firebase_credentials.json'));
    //         $messaging = $factory->createMessaging();

    //         $message = CloudMessage::withTarget('token', $notifiable->fcm_token)
    //             ->withNotification(
    //                 FirebaseNotification::create(__('notifications.customer.title'), $this->message)
    //             )
    //             ->withData([
    //                 'request_id' => $this->requestId,
    //             ]);


    //         $messaging->send($message);


    //         Log::info('✅ FCM Notification sent successfully!', [
    //             'user_id' => $notifiable->id,
    //             'fcm_token' => $notifiable->fcm_token,
    //             'request_id' => $this->requestId,
    //             'message' => $this->message
    //         ]);
    //     } catch (\Exception $e) {

    //         Log::error('❌ FCM Notification failed!', [
    //             'error' => $e->getMessage(),
    //             'user_id' => $notifiable->id ?? null,
    //             'request_id' => $this->requestId,
    //             'message' => $this->message
    //         ]);
    //     }
    // }


    public function toFcm($notifiable): FcmMessage
    {

        return FcmMessage::create()
            ->data([
                'request_id' => (string) $this->requestId,
                'message' => (string) $this->message
            ])
            ->notification(
                FcmNotification::create()
                    ->title(__('notifications.customer.title'))
                    ->body($this->message)
            );
    }



    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'request_id' => $this->requestId,
            'message' => $this->message
        ]);
    }
}
