<?php

namespace App\Policies;

use App\Models\User;
use App\Models\District;
use Illuminate\Auth\Access\HandlesAuthorization;

class DistrictPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_district');
    }
    public function view(User $user, District $district): bool
    {
        return $user->can('view_district');
    }
    public function create(User $user): bool
    {
        return $user->can('create_district');
    }
    public function update(User $user, District $district): bool
    {
        return $user->can('update_district');
    }
    public function delete(User $user, District $district): bool
    {
        return $user->can('delete_district');
    }
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_district');
    }
    public function forceDelete(User $user, District $district): bool
    {
        return $user->can('force_delete_district');
    }
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_district');
    }
    public function restore(User $user, District $district): bool
    {
        return $user->can('restore_district');
    }
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_district');
    }
    public function replicate(User $user, District $district): bool
    {
        return $user->can('replicate_district');
    }
    public function reorder(User $user): bool
    {
        return $user->can('reorder_district');
    }
}
