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
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->decimal('maintenance_fee', 10, 2)->default(0);
            $table->decimal('extra_hours', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('districts', function (Blueprint $table) {
            if (! Schema::hasColumn('districts', 'area_id')) {
                $table->foreignId('area_id')
                    ->nullable()
                    ->after('city_id')
                    ->constrained('areas')
                    ->nullOnDelete();
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'maintenance_fee')) {
                $table->decimal('maintenance_fee', 10, 2)
                    ->default(0)
                    ->after('hours');
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'maintenance_fee')) {
                $table->dropColumn('maintenance_fee');
            }
        });

        Schema::table('districts', function (Blueprint $table) {
            if (Schema::hasColumn('districts', 'area_id')) {
                $table->dropConstrainedForeignId('area_id');
            }
        });

        Schema::dropIfExists('areas');
    }
};
