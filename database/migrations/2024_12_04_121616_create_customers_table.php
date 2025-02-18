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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('phone', 15)->unique();
            $table->string('email')->unique()->nullable();
            $table->string('otp', 10)->nullable();
            $table->string('token')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->boolean('authorized')->default(false)->nullable();
            $table->boolean('activated')->default(false)->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
