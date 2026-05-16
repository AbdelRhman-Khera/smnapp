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
        Schema::table('technician_spare_part_requests', function (Blueprint $table) {
            $table->json('gr_response')->nullable();
            $table->timestamp('gr_sent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_spare_part_requests', function (Blueprint $table) {
            $table->dropColumn('gr_response');
            $table->dropColumn('gr_sent_at');
        });
    }
};
