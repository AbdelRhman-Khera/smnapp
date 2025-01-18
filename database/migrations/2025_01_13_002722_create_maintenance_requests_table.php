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
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->unsignedBigInteger('slot_id')->nullable();
            $table->enum('type', ['new_installation', 'regular_maintenance', 'emergency_maintenance']);
            $table->unsignedBigInteger('address_id')->nullable();
            $table->string('sap_order_id')->nullable();
            $table->text('problem_description')->nullable();
            $table->string('invoice_number')->nullable();
            $table->json('photos')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->json('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('slot_id')->references('id')->on('slots')->onDelete('set null');
            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('set null');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('set null');
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
