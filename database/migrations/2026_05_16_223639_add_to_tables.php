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
        Schema::table('invoices', function (Blueprint $table) {
            $table->text('machine_pic')->nullable();
        });
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->integer('is_product_delivered')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('machine_pic');
        });
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn('is_product_delivered');
        });
    }
};
