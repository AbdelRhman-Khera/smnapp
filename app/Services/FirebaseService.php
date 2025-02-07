<?php

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService {
    protected $messaging;

    public function __construct() {
        $factory = (new Factory)->withServiceAccount(storage_path('firebase-adminsdk.json'));
        $this->messaging = $factory->createMessaging();
    }

    public function sendPushNotification($deviceToken, $title, $body) {
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification(Notification::create($title, $body));

        $this->messaging->send($message);
    }
}


