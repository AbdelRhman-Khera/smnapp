<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_forms', function (Blueprint $table) {

            $table->string('name')->nullable()->after('user_id');
            $table->string('phone')->nullable()->after('name');
        });


        \DB::statement("ALTER TABLE support_forms MODIFY platform ENUM('app', 'web', 'chatbot') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('support_forms', function (Blueprint $table) {
            $table->dropColumn(['name', 'phone']);
        });


        \DB::statement("ALTER TABLE support_forms MODIFY platform ENUM('app', 'web') NOT NULL");
    }
};
