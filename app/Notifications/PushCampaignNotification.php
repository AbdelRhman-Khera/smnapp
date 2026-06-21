<?php

namespace App\Notifications;

use App\Models\PushNotificationCampaign;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class PushCampaignNotification extends Notification
{
    public function __construct(private PushNotificationCampaign $campaign)
    {
    }

    public function via($notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toArray($notifiable): array
    {
        [$title, $body, $locale] = $this->localizedContent($notifiable);

        return [
            'campaign_id' => $this->campaign->id,
            'title' => $title,
            'message' => $body,
            'deep_link' => $this->campaign->deep_link,
            'locale' => $locale,
        ];
    }

    public function toFcm($notifiable): FcmMessage
    {
        [$title, $body, $locale] = $this->localizedContent($notifiable);

        return FcmMessage::create()
            ->data([
                'campaign_id' => (string) $this->campaign->id,
                'deep_link' => (string) ($this->campaign->deep_link ?? ''),
                'locale' => $locale,
            ])
            ->notification(FcmNotification::create()
                ->title($title)
                ->body($body));
    }

    private function localizedContent($notifiable): array
    {
        $locale = $notifiable->preferred_locale === 'ar' ? 'ar' : 'en';

        return $locale === 'ar'
            ? [$this->campaign->title_ar, $this->campaign->body_ar, 'ar']
            : [$this->campaign->title_en, $this->campaign->body_en, 'en'];
    }
}
