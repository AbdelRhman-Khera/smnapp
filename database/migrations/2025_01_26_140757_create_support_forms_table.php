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
        Schema::create('support_forms', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('subject');
            $table->text('details');
            $table->enum('user_type', ['technician', 'customer']);
            $table->enum('platform', ['app', 'web']);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->json('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_forms');
    }
};
