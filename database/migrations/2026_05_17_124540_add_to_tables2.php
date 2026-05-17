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
        Schema::table('cities', function (Blueprint $table) {
            $table->integer('is_active')->default(1);
            $table->integer('hourly_rate')->default(0);
        });
        Schema::table('districts', function (Blueprint $table) {
            $table->integer('is_active')->default(1);
            $table->integer('hourly_rate')->default(0);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropColumn('hourly_rate');
        });
        Schema::table('districts', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropColumn('hourly_rate');
        });
    }
};
