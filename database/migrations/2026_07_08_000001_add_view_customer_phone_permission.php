<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'view_customer_phone',
            'guard_name' => 'web',
        ]);

        Role::where('name', 'super_admin')->first()?->givePermissionTo($permission);
    }

    public function down(): void
    {
        Permission::where('name', 'view_customer_phone')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
