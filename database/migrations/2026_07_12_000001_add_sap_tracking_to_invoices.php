<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('sap_sync_status', 20)->default('pending')->after('qr_code');
            $table->string('sap_sales_order_no')->nullable()->after('sap_sync_status');
            $table->text('sap_last_error')->nullable()->after('sap_sales_order_no');
        });

        Schema::table('sap_request_logs', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('maintenance_request_id')
                ->constrained('invoices')->nullOnDelete();
        });

        // Backfill: requests with a single invoice can safely inherit the
        // request-level SAP state onto that invoice.
        DB::statement("
            UPDATE invoices i
            JOIN maintenance_requests mr ON mr.id = i.maintenance_request_id
            SET i.sap_sync_status = COALESCE(mr.sap_sync_status, 'pending'),
                i.sap_sales_order_no = mr.sap_sales_order_no,
                i.sap_last_error = mr.sap_last_error
            WHERE (
                SELECT COUNT(*) FROM (SELECT id, maintenance_request_id, deleted_at FROM invoices) i2
                WHERE i2.maintenance_request_id = mr.id AND i2.deleted_at IS NULL
            ) = 1
        ");

        // An invoice holding a SAP QR definitely synced successfully.
        DB::statement("
            UPDATE invoices
            SET sap_sync_status = 'success', sap_last_error = NULL
            WHERE qr_code IS NOT NULL AND qr_code != ''
        ");

        DB::statement("
            UPDATE sap_request_logs l
            JOIN maintenance_requests mr ON mr.id = l.maintenance_request_id
            SET l.invoice_id = (
                SELECT i.id FROM invoices i
                WHERE i.maintenance_request_id = mr.id AND i.deleted_at IS NULL
                LIMIT 1
            )
            WHERE l.invoice_id IS NULL
              AND (
                SELECT COUNT(*) FROM invoices i2
                WHERE i2.maintenance_request_id = mr.id AND i2.deleted_at IS NULL
              ) = 1
        ");
    }

    public function down(): void
    {
        Schema::table('sap_request_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['sap_sync_status', 'sap_sales_order_no', 'sap_last_error']);
        });
    }
};
