<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PayTabsGateway
{
    /**
     * Query PayTabs for the real state of a transaction.
     * Returns the raw PayTabs response array, or null when the query
     * itself could not be performed (network / config problem).
     */
    public function verifyTransaction(string $tranRef): ?array
    {
        try {
            $response = Http::withHeaders([
                'authorization' => (string) config('paytabs.server_key'),
            ])
                ->timeout(15)
                ->post($this->baseUrl() . '/payment/query', [
                    'profile_id' => (int) config('paytabs.profile_id'),
                    'tran_ref' => $tranRef,
                ]);

            if (! $response->successful()) {
                Log::channel('PayTabs')->warning('[verify] PayTabs query returned non-success HTTP status', [
                    'tran_ref' => $tranRef,
                    'http_status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (Throwable $exception) {
            Log::channel('PayTabs')->error('[verify] PayTabs query failed', [
                'tran_ref' => $tranRef,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function isAuthorized(?array $verification): bool
    {
        return data_get($verification, 'payment_result.response_status') === 'A';
    }

    private function baseUrl(): string
    {
        return match (strtoupper((string) config('paytabs.region'))) {
            'SAU' => 'https://secure.paytabs.sa',
            'ARE' => 'https://secure.paytabs.com',
            'EGY' => 'https://secure-egypt.paytabs.com',
            'OMN' => 'https://secure-oman.paytabs.com',
            'JOR' => 'https://secure-jordan.paytabs.com',
            default => 'https://secure-global.paytabs.com',
        };
    }
}
