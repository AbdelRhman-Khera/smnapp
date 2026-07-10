<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\PushNotificationCampaign;
use App\Models\Technician;
use App\Services\PushNotificationCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushCampaignChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * FCM failures per recipient are caught and logged inside the service,
     * so a retry would double-send to recipients that already succeeded.
     */
    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public int $campaignId,
        public array $recipientIds,
    ) {}

    public function handle(PushNotificationCampaignService $service): void
    {
        $campaign = PushNotificationCampaign::find($this->campaignId);

        if (! $campaign) {
            return;
        }

        $model = $campaign->audience === 'technician' ? Technician::class : Customer::class;

        $recipients = $model::query()
            ->whereIn('id', $this->recipientIds)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->get();

        $successCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            $service->sendToRecipient($campaign, $recipient)
                ? $successCount++
                : $failedCount++;
        }

        if ($successCount > 0) {
            $campaign->increment('last_success_count', $successCount);
        }

        if ($failedCount > 0) {
            $campaign->increment('last_failed_count', $failedCount);
        }
    }
}
