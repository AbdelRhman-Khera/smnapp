<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE maintenance_requests MODIFY type ENUM('new_installation', 'regular_maintenance', 'emergency_maintenance', 'warranty') NOT NULL");
        DB::statement("ALTER TABLE request_statuses MODIFY status ENUM('pending', 'visit_payment_pending', 'service_paid', 'technician_assigned', 'technician_on_the_way', 'technician_arrived', 'in_progress', 'waiting_for_payment', 'waiting_for_technician_confirm_payment', 'completed', 'canceled') NOT NULL");

        Schema::table('maintenance_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('maintenance_requests', 'warranty_source_request_id')) {
                $table->foreignId('warranty_source_request_id')
                    ->nullable()
                    ->after('type')
                    ->constrained('maintenance_requests')
                    ->nullOnDelete();
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'invoice_type')) {
                $table->string('invoice_type')
                    ->default('final')
                    ->after('maintenance_request_id')
                    ->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'invoice_type')) {
                $table->dropIndex(['invoice_type']);
                $table->dropColumn('invoice_type');
            }
        });

        Schema::table('maintenance_requests', function (Blueprint $table) {
            if (Schema::hasColumn('maintenance_requests', 'warranty_source_request_id')) {
                $table->dropConstrainedForeignId('warranty_source_request_id');
            }
        });

        DB::statement("ALTER TABLE request_statuses MODIFY status ENUM('pending', 'technician_assigned', 'technician_on_the_way', 'technician_arrived', 'in_progress', 'waiting_for_payment', 'waiting_for_technician_confirm_payment', 'completed', 'canceled') NOT NULL");
        DB::statement("ALTER TABLE maintenance_requests MODIFY type ENUM('new_installation', 'regular_maintenance', 'emergency_maintenance') NOT NULL");
    }
};
