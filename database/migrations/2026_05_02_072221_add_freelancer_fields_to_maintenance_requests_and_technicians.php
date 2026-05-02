<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->boolean('is_freelancer')->default(false)->after('activated');
            $table->index('is_freelancer');
        });

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->boolean('is_open_for_freelancers')->default(false)->after('last_status');
            $table->timestamp('opened_for_freelancers_at')->nullable()->after('is_open_for_freelancers');
            $table->timestamp('freelancer_assigned_at')->nullable()->after('opened_for_freelancers_at');

            $table->index('is_open_for_freelancers');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropIndex(['is_open_for_freelancers']);
            $table->dropColumn([
                'is_open_for_freelancers',
                'opened_for_freelancers_at',
                'freelancer_assigned_at',
            ]);
        });

        Schema::table('technicians', function (Blueprint $table) {
            $table->dropIndex(['is_freelancer']);
            $table->dropColumn('is_freelancer');
        });
    }
};
