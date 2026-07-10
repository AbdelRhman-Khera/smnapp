<?php

namespace App\Services;

use App\Jobs\SendPushCampaignChunkJob;
use App\Models\Customer;
use App\Models\PushNotificationCampaign;
use App\Models\PushNotificationSend;
use App\Models\Technician;
use App\Notifications\PushCampaignNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class PushNotificationCampaignService
{
    /**
     * Queue the campaign in chunks so large audiences are sent by the
     * queue worker in the background instead of within the HTTP request.
     */
    public function queue(PushNotificationCampaign $campaign, int $chunkSize = 100): array
    {
        $recipientIds = $this->recipientQuery($campaign)->pluck('id');

        $campaign->update([
            'send_count' => $campaign->send_count + 1,
            'last_targeted_count' => $recipientIds->count(),
            'last_success_count' => 0,
            'last_failed_count' => 0,
            'last_sent_at' => now(),
        ]);

        $chunks = $recipientIds->chunk($chunkSize);

        foreach ($chunks as $chunk) {
            SendPushCampaignChunkJob::dispatch($campaign->id, $chunk->values()->all());
        }

        return [
            'targeted' => $recipientIds->count(),
            'jobs' => $chunks->count(),
        ];
    }

    /**
     * Synchronous send. Kept for small audiences / programmatic use —
     * prefer queue() for anything user-facing.
     */
    public function send(PushNotificationCampaign $campaign): array
    {
        $recipients = $this->recipientsFor($campaign);
        $successCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            $this->sendToRecipient($campaign, $recipient)
                ? $successCount++
                : $failedCount++;
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

    public function sendToRecipient(PushNotificationCampaign $campaign, Customer|Technician $recipient): bool
    {
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

            return true;
        } catch (Throwable $exception) {
            PushNotificationSend::create([
                'push_notification_campaign_id' => $campaign->id,
                'recipient_type' => $campaign->audience,
                'recipient_id' => $recipient->id,
                'locale' => $locale,
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function recipientsFor(PushNotificationCampaign $campaign): Collection
    {
        return $this->recipientQuery($campaign)->get();
    }

    private function recipientQuery(PushNotificationCampaign $campaign): Builder
    {
        $model = $campaign->audience === 'technician' ? Technician::class : Customer::class;

        return $model::query()
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->when(
                $campaign->recipient_scope === 'selected',
                fn ($query) => $query->whereIn('id', $campaign->recipient_ids ?? [])
            );
    }
}
