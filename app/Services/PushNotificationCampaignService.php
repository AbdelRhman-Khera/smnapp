<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PushNotificationCampaign;
use App\Models\PushNotificationSend;
use App\Models\Technician;
use App\Notifications\PushCampaignNotification;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class PushNotificationCampaignService
{
    public function send(PushNotificationCampaign $campaign): array
    {
        $recipients = $this->recipientsFor($campaign);
        $successCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            $locale = $recipient->preferred_locale === 'ar' ? 'ar' : 'en';

            try {
                $recipient->notify(new PushCampaignNotification($campaign));

                PushNotificationSend::create([
                    'push_notification_campaign_id' => $campaign->id,
                    'recipient_type' => $campaign->audience,
                    'recipient_id' => $recipient->id,
                    'locale' => $locale,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                $successCount++;
            } catch (Throwable $exception) {
                PushNotificationSend::create([
                    'push_notification_campaign_id' => $campaign->id,
                    'recipient_type' => $campaign->audience,
                    'recipient_id' => $recipient->id,
                    'locale' => $locale,
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                ]);
                $failedCount++;
            }
        }

        $campaign->update([
            'send_count' => $campaign->send_count + 1,
            'last_targeted_count' => $recipients->count(),
            'last_success_count' => $successCount,
            'last_failed_count' => $failedCount,
            'last_sent_at' => now(),
        ]);

        return [
            'targeted' => $recipients->count(),
            'success' => $successCount,
            'failed' => $failedCount,
        ];
    }

    private function recipientsFor(PushNotificationCampaign $campaign): Collection
    {
        $model = $campaign->audience === 'technician' ? Technician::class : Customer::class;

        return $model::query()
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->when(
                $campaign->recipient_scope === 'selected',
                fn ($query) => $query->whereIn('id', $campaign->recipient_ids ?? [])
            )
            ->get();
    }
}
