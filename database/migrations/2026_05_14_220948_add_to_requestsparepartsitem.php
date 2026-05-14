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
        Schema::table('technician_spare_part_request_items', function (Blueprint $table) {
            $table->string('item_no')->nullable()->after('approved_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_spare_part_request_items', function (Blueprint $table) {
            $table->dropColumn('item_no');
        });
    }
};
