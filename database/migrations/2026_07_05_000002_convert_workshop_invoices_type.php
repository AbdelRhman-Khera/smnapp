<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Workshop repair invoices created from device withdrawal follow-up requests
     * were stored as 'final'. They now use their own 'workshop' invoice type so
     * paying them moves the request to 'service_paid' while paying a real final
     * invoice completes the request.
     */
    public function up(): void
    {
        DB::table('invoices')
            ->where('invoice_type', 'final')
            ->where('notes', 'like', '%device_withdrawal_request%')
            ->update(['invoice_type' => 'workshop']);
    }

    public function down(): void
    {
        DB::table('invoices')
            ->where('invoice_type', 'workshop')
            ->update(['invoice_type' => 'final']);
    }
};
