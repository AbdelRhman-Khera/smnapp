<?php

namespace App\Jobs;

use App\Http\Controllers\SapController;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendInvoiceToSapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * A retry could create a duplicate SAP sales order when the first
     * attempt succeeded on the SAP side but the response was lost, so
     * failures are left for the manual "Send to SAP" action instead.
     */
    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public int $invoiceId,
        public string $paymentMethod,
    ) {}

    /**
     * Mark the invoice as queued and dispatch the job.
     */
    public static function queueFor(Invoice $invoice, string $paymentMethod): void
    {
        $invoice->update(['sap_sync_status' => 'queued']);

        static::dispatch($invoice->id, $paymentMethod);
    }

    public function handle(): void
    {
        $invoice = Invoice::with(['maintenanceRequest', 'services', 'spareParts'])->find($this->invoiceId);

        if (! $invoice || ! $invoice->maintenanceRequest) {
            Log::warning('[SendInvoiceToSapJob] Invoice or maintenance request not found', [
                'invoice_id' => $this->invoiceId,
            ]);

            return;
        }

        app(SapController::class)->createSalesOrder(
            $invoice->maintenanceRequest,
            $this->paymentMethod,
            $invoice,
        );
    }
}
