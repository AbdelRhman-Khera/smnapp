<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('sap_id')
                    ->constrained('branches')
                    ->nullOnDelete();
            }
        });

        Schema::create('device_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained('maintenance_requests')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('technicians')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('follow_up_maintenance_request_id')
                ->nullable()
                ->constrained('maintenance_requests')
                ->nullOnDelete();
            $table->string('status')->default('pending_customer_approval')->index();
            $table->json('customer_decision_notes')->nullable();
            $table->text('technician_notes')->nullable();
            $table->text('branch_notes')->nullable();
            $table->text('workshop_notes')->nullable();
            $table->timestamp('customer_decision_at')->nullable();
            $table->timestamp('delivered_to_branch_at')->nullable();
            $table->timestamp('received_by_branch_at')->nullable();
            $table->timestamp('repair_started_at')->nullable();
            $table->timestamp('repair_completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('device_withdrawal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_withdrawal_request_id')
                ->constrained('device_withdrawal_requests')
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('serial_number')->nullable();
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();
            $table->string('status')->default('pending_customer_approval')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_withdrawal_items');
        Schema::dropIfExists('device_withdrawal_requests');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }
};
