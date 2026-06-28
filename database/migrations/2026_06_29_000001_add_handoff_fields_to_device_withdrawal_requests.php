<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_withdrawal_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('device_withdrawal_requests', 'handoff_technician_id')) {
                $table->foreignId('handoff_technician_id')
                    ->nullable()
                    ->after('technician_id')
                    ->constrained('technicians')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('device_withdrawal_requests', 'handoff_notes')) {
                $table->text('handoff_notes')->nullable()->after('technician_notes');
            }

            if (! Schema::hasColumn('device_withdrawal_requests', 'customer_delivery_notes')) {
                $table->text('customer_delivery_notes')->nullable()->after('workshop_notes');
            }

            if (! Schema::hasColumn('device_withdrawal_requests', 'assigned_to_handoff_technician_at')) {
                $table->timestamp('assigned_to_handoff_technician_at')->nullable()->after('customer_decision_at');
            }

            if (! Schema::hasColumn('device_withdrawal_requests', 'received_by_handoff_technician_at')) {
                $table->timestamp('received_by_handoff_technician_at')->nullable()->after('assigned_to_handoff_technician_at');
            }

            if (! Schema::hasColumn('device_withdrawal_requests', 'delivered_to_customer_at')) {
                $table->timestamp('delivered_to_customer_at')->nullable()->after('repair_completed_at');
            }

            if (! Schema::hasColumn('device_withdrawal_requests', 'customer_received_at')) {
                $table->timestamp('customer_received_at')->nullable()->after('delivered_to_customer_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('device_withdrawal_requests', function (Blueprint $table) {
            if (Schema::hasColumn('device_withdrawal_requests', 'handoff_technician_id')) {
                $table->dropConstrainedForeignId('handoff_technician_id');
            }

            foreach ([
                'handoff_notes',
                'customer_delivery_notes',
                'assigned_to_handoff_technician_at',
                'received_by_handoff_technician_at',
                'delivered_to_customer_at',
                'customer_received_at',
            ] as $column) {
                if (Schema::hasColumn('device_withdrawal_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
