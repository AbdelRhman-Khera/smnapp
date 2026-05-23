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
            $table->json('request_payload')->nullable()->after('response');
            $table->json('gr_request_payload')->nullable()->after('gr_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_spare_part_requests', function (Blueprint $table) {
            $table->dropColumn(['request_payload', 'gr_request_payload']);
        });
    }
};
