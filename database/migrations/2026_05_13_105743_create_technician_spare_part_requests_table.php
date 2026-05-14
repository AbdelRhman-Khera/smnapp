<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_spare_part_requests', function (Blueprint $table) {

            $table->id();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('technician_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sap_ref')->index()->nullable();
            $table->json('response')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('delivered_at')->nullable();
            $table->string('status')->default('pending');
            // pending / created / failed / to_be_delivered / delivered /

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_spare_part_requests');
    }
};
