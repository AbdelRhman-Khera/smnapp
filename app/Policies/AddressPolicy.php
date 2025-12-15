<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Address;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_address');
    }

    public function view(User $user, Address $address): bool
    {
        return $user->can('view_address');
    }

    public function create(User $user): bool
    {
        return $user->can('create_address');
    }

    public function update(User $user, Address $address): bool
    {
        return $user->can('update_address');
    }

    public function delete(User $user, Address $address): bool
    {
        return $user->can('delete_address');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_address');
    }

    public function forceDelete(User $user, Address $address): bool
    {
        return $user->can('force_delete_address');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_address');
    }

    public function restore(User $user, Address $address): bool
    {
        return $user->can('restore_address');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_address');
    }

    public function replicate(User $user, Address $address): bool
    {
        return $user->can('replicate_address');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_address');
    }
}
