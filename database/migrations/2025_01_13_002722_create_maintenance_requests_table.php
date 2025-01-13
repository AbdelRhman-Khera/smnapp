<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->enum('type', ['new_installation', 'regular_maintenance', 'emergency_maintenance']);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('address_id');
            $table->date('date');
            $table->json('preferred_times');
            $table->string('confirmed_time')->nullable();
            $table->string('sap_order_id')->nullable();
            $table->text('problem_description')->nullable();
            $table->string('invoice_number')->nullable();
            $table->json('photos')->nullable();
            $table->date('last_maintenance_date')->nullable();
            // $table->enum('status', [
            //     'pending',
            //     'technician_assigned',
            //     'technician_on_the_way',
            //     'technician_arrived',
            //     'in_progress',
            //     'waiting_for_payment',
            //     'paid',
            // ])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
