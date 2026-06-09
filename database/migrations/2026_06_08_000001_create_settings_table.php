<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general')->index();
            $table->string('key')->unique();
            $table->string('label')->nullable();
            $table->string('type')->default('json');
            $table->json('value')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('settings')->insert([
            'group' => 'payments',
            'key' => 'payment_methods',
            'label' => 'Payment Methods',
            'type' => 'payment_methods',
            'value' => json_encode([
                [
                    'code' => 'online',
                    'label_en' => 'Online Payment',
                    'label_ar' => 'الدفع الإلكتروني',
                    'is_active' => true,
                    'sort_order' => 10,
                ],
                [
                    'code' => 'machine',
                    'label_en' => 'POS Machine',
                    'label_ar' => 'الدفع بالشبكة',
                    'is_active' => true,
                    'sort_order' => 20,
                ],
                [
                    'code' => 'cash',
                    'label_en' => 'Cash',
                    'label_ar' => 'كاش',
                    'is_active' => true,
                    'sort_order' => 30,
                ],
                [
                    'code' => 'remittance',
                    'label_en' => 'Bank Remittance',
                    'label_ar' => 'تحويل بنكي',
                    'is_active' => false,
                    'sort_order' => 40,
                ],
            ], JSON_UNESCAPED_UNICODE),
            'description' => 'Controls which payment methods appear in the mobile app.',
            'is_public' => true,
            'sort_order' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
