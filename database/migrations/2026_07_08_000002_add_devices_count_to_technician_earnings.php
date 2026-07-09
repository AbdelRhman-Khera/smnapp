<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technician_earnings', function (Blueprint $table) {
            if (! Schema::hasColumn('technician_earnings', 'devices_count')) {
                $table->unsignedInteger('devices_count')->default(1)->after('request_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('technician_earnings', function (Blueprint $table) {
            if (Schema::hasColumn('technician_earnings', 'devices_count')) {
                $table->dropColumn('devices_count');
            }
        });
    }
};
