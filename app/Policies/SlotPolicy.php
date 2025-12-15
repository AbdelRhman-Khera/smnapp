<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Slot;
use Illuminate\Auth\Access\HandlesAuthorization;

class SlotPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_slot');
    }
    public function view(User $user, Slot $slot): bool
    {
        return $user->can('view_slot');
    }
    public function create(User $user): bool
    {
        return $user->can('create_slot');
    }
    public function update(User $user, Slot $slot): bool
    {
        return $user->can('update_slot');
    }
    public function delete(User $user, Slot $slot): bool
    {
        return $user->can('delete_slot');
    }
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_slot');
    }
    public function forceDelete(User $user, Slot $slot): bool
    {
        return $user->can('force_delete_slot');
    }
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_slot');
    }
    public function restore(User $user, Slot $slot): bool
    {
        return $user->can('restore_slot');
    }
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_slot');
    }
    public function replicate(User $user, Slot $slot): bool
    {
        return $user->can('replicate_slot');
    }
    public function reorder(User $user): bool
    {
        return $user->can('reorder_slot');
    }
}
