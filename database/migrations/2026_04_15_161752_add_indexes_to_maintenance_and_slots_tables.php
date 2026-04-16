<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->index('technician_id');
            $table->index('slot_id');
            $table->index('last_status');
        });

        Schema::table('slots', function (Blueprint $table) {
            $table->index('date');
            $table->index(['technician_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropIndex(['technician_id']);
            $table->dropIndex(['slot_id']);
            $table->dropIndex(['last_status']);
        });

        Schema::table('slots', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['technician_id', 'date']);
        });
    }
};
