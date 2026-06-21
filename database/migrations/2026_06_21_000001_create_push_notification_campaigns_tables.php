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
        Schema::create('push_notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->enum('audience', ['customer', 'technician']);
            $table->enum('recipient_scope', ['all', 'selected'])->default('all');
            $table->json('recipient_ids')->nullable();
            $table->string('title_ar');
            $table->string('title_en');
            $table->text('body_ar');
            $table->text('body_en');
            $table->string('deep_link')->nullable();
            $table->unsignedInteger('send_count')->default(0);
            $table->unsignedInteger('last_targeted_count')->default(0);
            $table->unsignedInteger('last_success_count')->default(0);
            $table->unsignedInteger('last_failed_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('push_notification_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('push_notification_campaign_id')
                ->constrained('push_notification_campaigns')
                ->cascadeOnDelete();
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_id');
            $table->string('locale', 5)->default('en');
            $table->enum('status', ['sent', 'failed']);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_type', 'recipient_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'preferred_locale')) {
                $table->string('preferred_locale', 5)->default('ar')->after('fcm_token');
            }
        });

        Schema::table('technicians', function (Blueprint $table) {
            if (! Schema::hasColumn('technicians', 'preferred_locale')) {
                $table->string('preferred_locale', 5)->default('en')->after('fcm_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            if (Schema::hasColumn('technicians', 'preferred_locale')) {
                $table->dropColumn('preferred_locale');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'preferred_locale')) {
                $table->dropColumn('preferred_locale');
            }
        });

        Schema::dropIfExists('push_notification_sends');
        Schema::dropIfExists('push_notification_campaigns');
    }
};
