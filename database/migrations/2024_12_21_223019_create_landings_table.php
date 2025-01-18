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
        Schema::create('landings', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('main_title_ar')->nullable();
            $table->string('main_title_en')->nullable();
            $table->text('main_description_ar')->nullable();
            $table->text('main_description_en')->nullable();
            $table->string('main_image')->nullable();

            $table->string('feature_title_ar')->nullable();
            $table->string('feature_title_en')->nullable();
            $table->text('feature_description_ar')->nullable();
            $table->text('feature_description_en')->nullable();
            $table->string('feature_image')->nullable();

            $table->json('steps')->nullable();

            $table->json('services')->nullable();
            $table->string('services_image')->nullable();

            $table->string('store_title_ar')->nullable();
            $table->string('store_title_en')->nullable();
            $table->text('store_description_ar')->nullable();
            $table->text('store_description_en')->nullable();
            $table->string('store_image')->nullable();
            $table->string('store_url')->nullable();

            $table->string('map_image')->nullable();

            $table->string('download_title_ar')->nullable();
            $table->string('download_title_en')->nullable();
            $table->string('app_store_url')->nullable();
            $table->string('google_play_url')->nullable();
            $table->string('download_image')->nullable();

            $table->json('social')->nullable();
            $table->string('rights_ar')->nullable();
            $table->string('rights_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landings');
    }
};
