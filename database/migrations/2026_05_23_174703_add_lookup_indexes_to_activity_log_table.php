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
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id'], 'activity_log_subject_lookup_index');
            $table->index(['causer_type', 'causer_id'], 'activity_log_causer_lookup_index');
            $table->index('event', 'activity_log_event_lookup_index');
            $table->index('created_at', 'activity_log_created_at_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->dropIndex('activity_log_subject_lookup_index');
            $table->dropIndex('activity_log_causer_lookup_index');
            $table->dropIndex('activity_log_event_lookup_index');
            $table->dropIndex('activity_log_created_at_lookup_index');
        });
    }
};
