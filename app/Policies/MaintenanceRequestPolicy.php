<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MaintenanceRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenanceRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_maintenance_request');
    }
    public function view(User $user, MaintenanceRequest $request): bool
    {
        return $user->can('view_maintenance_request');
    }
    public function create(User $user): bool
    {
        return $user->can('create_maintenance_request');
    }
    public function update(User $user, MaintenanceRequest $request): bool
    {
        return $user->can('update_maintenance_request');
    }
    public function delete(User $user, MaintenanceRequest $request): bool
    {
        return $user->can('delete_maintenance_request');
    }
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_maintenance_request');
    }
    public function forceDelete(User $user, MaintenanceRequest $request): bool
    {
        return $user->can('force_delete_maintenance_request');
    }
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_maintenance_request');
    }
    public function restore(User $user, MaintenanceRequest $request): bool
    {
        return $user->can('restore_maintenance_request');
    }
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_maintenance_request');
    }
    public function replicate(User $user, MaintenanceRequest $request): bool
    {
        return $user->can('replicate_maintenance_request');
    }
    public function reorder(User $user): bool
    {
        return $user->can('reorder_maintenance_request');
    }
}
