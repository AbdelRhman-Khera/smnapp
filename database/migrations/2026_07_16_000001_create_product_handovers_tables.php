<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'canceled'])->default('pending')->index();
            $table->text('notes')->nullable();
            $table->text('technician_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_handover_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_handover_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('serial_number');
            $table->timestamps();

            $table->index('serial_number');
        });

        Schema::table('maintenance_requests', function (Blueprint $table) {
            // Set to true when the technician accepts a product handover.
            $table->boolean('technician_received_products')->default(false)->after('is_product_delivered');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn('technician_received_products');
        });

        Schema::dropIfExists('product_handover_items');
        Schema::dropIfExists('product_handovers');
    }
};
