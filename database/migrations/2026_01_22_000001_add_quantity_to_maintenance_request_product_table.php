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
        Schema::table('maintenance_request_product', function (Blueprint $table) {
            // Default to 1 to preserve existing behavior for old records.
            $table->unsignedInteger('quantity')->default(1)->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_request_product', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
