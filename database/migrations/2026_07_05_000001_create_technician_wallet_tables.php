<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->unsignedInteger('requests_count')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('technician_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('request_type', 50);
            $table->decimal('amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'requested', 'paid'])->default('pending')->index();
            $table->foreignId('payout_request_id')->nullable()->constrained('technician_payout_requests')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            'group' => 'technicians',
            'key' => 'technician_maintenance_fees',
            'label' => 'Technician Maintenance Fees',
            'type' => 'technician_fees',
            'value' => json_encode([
                [
                    'type' => 'new_installation',
                    'label_en' => 'New Installation',
                    'label_ar' => 'تركيب جديد',
                    'fee' => 35,
                ],
                [
                    'type' => 'regular_maintenance',
                    'label_en' => 'Regular Maintenance',
                    'label_ar' => 'صيانة دورية',
                    'fee' => 30,
                ],
                [
                    'type' => 'emergency_maintenance',
                    'label_en' => 'Emergency Maintenance',
                    'label_ar' => 'صيانة طارئة',
                    'fee' => 30,
                ],
                [
                    'type' => 'warranty',
                    'label_en' => 'Warranty',
                    'label_ar' => 'ضمان',
                    'fee' => 30,
                ],
            ], JSON_UNESCAPED_UNICODE),
            'description' => 'Fee credited to the technician wallet for each completed maintenance request, by request type.',
            'is_public' => false,
            'sort_order' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_earnings');
        Schema::dropIfExists('technician_payout_requests');

        DB::table('settings')->where('key', 'technician_maintenance_fees')->delete();
    }
};
