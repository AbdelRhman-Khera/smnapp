<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $widgets = [
        'widget_StatsOverview',
        'widget_RevenueByPaymentMethodChart',
        'widget_AvgInvoiceValueChart',
        'widget_RequestsHeatmapChart',
        'widget_CancelledRequestsChart',
        'widget_WarrantyRateChart',
        'widget_DeviceWithdrawalsByStatusChart',
        'widget_TechnicianRatingChart',
        'widget_FreelancerClaimChart',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::where('name', 'super_admin')->first();

        foreach ($this->widgets as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $superAdmin?->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->widgets)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
