<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_spare_part_request_items', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('technician_spare_part_request_id');

            $table->foreign(
                'technician_spare_part_request_id',
                'tsp_request_items_request_fk'
            )
                ->references('id')
                ->on('technician_spare_part_requests')
                ->onDelete('cascade');
            // $table->foreignId('technician_spare_part_request_id')
            //     ->constrained()
            //     ->cascadeOnDelete();

            $table->foreignId('spare_part_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->integer('approved_quantity')
                ->nullable();


            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_spare_part_request_items');
    }
};
