<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->string('sap_sync_status')->default('pending')->after('last_status'); // pending, queued, success, failed
            $table->string('sap_sales_order_no')->nullable()->after('sap_sync_status');
            $table->text('sap_last_error')->nullable()->after('sap_sales_order_no');
            $table->integer('hours')->default(1)->nullable()->after('sap_last_error');
            $table->string('extra_slot_id')->nullable()->after('slot_id');
            $table->index('sap_sync_status');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropIndex(['sap_sync_status']);
            $table->dropColumn([
                'sap_sync_status',
                'sap_sales_order_no',
                'sap_last_error',
                'hours',
                'extra_slot_id',
            ]);
        });
    }
};
