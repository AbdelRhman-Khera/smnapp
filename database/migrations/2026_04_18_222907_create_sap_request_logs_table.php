<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sap_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->nullable()->constrained()->nullOnDelete();

            $table->string('action')->default('create_sales_order');
            $table->string('payment_method')->nullable();

            $table->string('http_method')->nullable();
            $table->text('url')->nullable();
            $table->integer('http_status')->nullable();

            $table->string('sap_status')->nullable();   // S / E
            $table->text('sap_desc')->nullable();       // order no or error text

            $table->longText('request_payload')->nullable();
            $table->longText('response_body')->nullable();
            $table->longText('error_message')->nullable();

            $table->boolean('is_success')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('maintenance_request_id');
            $table->index('sap_status');
            $table->index('is_success');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sap_request_logs');
    }
};
