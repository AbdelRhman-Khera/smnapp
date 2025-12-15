<?php

namespace App\Policies;

use App\Models\User;
use App\Models\City;
use Illuminate\Auth\Access\HandlesAuthorization;

class CityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_city');
    }
    public function view(User $user, City $city): bool
    {
        return $user->can('view_city');
    }
    public function create(User $user): bool
    {
        return $user->can('create_city');
    }
    public function update(User $user, City $city): bool
    {
        return $user->can('update_city');
    }
    public function delete(User $user, City $city): bool
    {
        return $user->can('delete_city');
    }
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_city');
    }
    public function forceDelete(User $user, City $city): bool
    {
        return $user->can('force_delete_city');
    }
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_city');
    }
    public function restore(User $user, City $city): bool
    {
        return $user->can('restore_city');
    }
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_city');
    }
    public function replicate(User $user, City $city): bool
    {
        return $user->can('replicate_city');
    }
    public function reorder(User $user): bool
    {
        return $user->can('reorder_city');
    }
}
