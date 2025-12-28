<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Landing;
use Illuminate\Auth\Access\HandlesAuthorization;

class LandingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_landing::page');
    }
    public function view(User $user, Landing $landing): bool
    {
        return $user->can('view_landing::page');
    }
    public function create(User $user): bool
    {
        return $user->can('create_landing::page');
    }
    public function update(User $user, Landing $landing): bool
    {
        return $user->can('update_landing::page');
    }
    public function delete(User $user, Landing $landing): bool
    {
        return $user->can('delete_landing::page');
    }
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_landing::page');
    }
    public function forceDelete(User $user, Landing $landing): bool
    {
        return $user->can('force_delete_landing::page');
    }
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_landing::page');
    }
    public function restore(User $user, Landing $landing): bool
    {
        return $user->can('restore_landing::page');
    }
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_landing::page');
    }
    public function replicate(User $user, Landing $landing): bool
    {
        return $user->can('replicate_landing::page');
    }
    public function reorder(User $user): bool
    {
        return $user->can('reorder_landing::page');
    }
}
