<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SupportForm;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupportFormPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_support::form');
    }
    public function view(User $user, SupportForm $form): bool
    {
        return $user->can('view_support::form');
    }
    public function create(User $user): bool
    {
        return $user->can('create_support::form');
    }
    public function update(User $user, SupportForm $form): bool
    {
        return $user->can('update_support::form');
    }
    public function delete(User $user, SupportForm $form): bool
    {
        return $user->can('delete_support::form');
    }
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_support::form');
    }
    public function forceDelete(User $user, SupportForm $form): bool
    {
        return $user->can('force_delete_support::form');
    }
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_support::form');
    }
    public function restore(User $user, SupportForm $form): bool
    {
        return $user->can('restore_support::form');
    }
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_support::form');
    }
    public function replicate(User $user, SupportForm $form): bool
    {
        return $user->can('replicate_support::form');
    }
    public function reorder(User $user): bool
    {
        return $user->can('reorder_support::form');
    }
}
