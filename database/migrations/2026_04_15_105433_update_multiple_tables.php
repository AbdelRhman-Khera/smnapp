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
        // Update users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('sap_id')->nullable()->default('18002W03')->after('email');
        });

        // Update technicians table
        Schema::table('technicians', function (Blueprint $table) {
            $table->string('sap_id')->nullable()->after('email');
            $table->string('site_id')->nullable()->after('sap_id');
        });

        // Update maintenance requests table
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->string('entry_sap_id')->nullable()->default('18002W03')->after('type');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
