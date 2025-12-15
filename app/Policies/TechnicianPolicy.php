<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Technician;
use Illuminate\Auth\Access\HandlesAuthorization;

class TechnicianPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_technician');
    }
    public function view(User $user, Technician $technician): bool
    {
        return $user->can('view_technician');
    }
    public function create(User $user): bool
    {
        return $user->can('create_technician');
    }
    public function update(User $user, Technician $technician): bool
    {
        return $user->can('update_technician');
    }
    public function delete(User $user, Technician $technician): bool
    {
        return $user->can('delete_technician');
    }
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_technician');
    }
    public function forceDelete(User $user, Technician $technician): bool
    {
        return $user->can('force_delete_technician');
    }
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_technician');
    }
    public function restore(User $user, Technician $technician): bool
    {
        return $user->can('restore_technician');
    }
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_technician');
    }
    public function replicate(User $user, Technician $technician): bool
    {
        return $user->can('replicate_technician');
    }
    public function reorder(User $user): bool
    {
        return $user->can('reorder_technician');
    }
}
