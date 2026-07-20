<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            // 'store': product bought from Samnan (free installation).
            // 'external': product bought elsewhere (visit fee applies).
            $table->string('purchase_source', 20)->nullable()->after('type');
        });

        Schema::table('products', function (Blueprint $table) {
            // Hours used for new_installation requests. Falls back to the
            // regular maintenance `hours` value when null.
            $table->decimal('installation_hours', 8, 2)->nullable()->after('hours');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn('purchase_source');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('installation_hours');
        });
    }
};
