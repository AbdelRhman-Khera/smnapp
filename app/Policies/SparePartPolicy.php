<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SparePart;
use Illuminate\Auth\Access\HandlesAuthorization;

class SparePartPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_spare::part');
    }
    public function view(User $user, SparePart $sparePart): bool
    {
        return $user->can('view_spare::part');
    }
    public function create(User $user): bool
    {
        return $user->can('create_spare::part');
    }
    public function update(User $user, SparePart $sparePart): bool
    {
        return $user->can('update_spare::part');
    }
    public function delete(User $user, SparePart $sparePart): bool
    {
        return $user->can('delete_spare::part');
    }
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_spare::part');
    }
    public function forceDelete(User $user, SparePart $sparePart): bool
    {
        return $user->can('force_delete_spare::part');
    }
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_spare::part');
    }
    public function restore(User $user, SparePart $sparePart): bool
    {
        return $user->can('restore_spare::part');
    }
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_spare::part');
    }
    public function replicate(User $user, SparePart $sparePart): bool
    {
        return $user->can('replicate_spare::part');
    }
    public function reorder(User $user): bool
    {
        return $user->can('reorder_spare::part');
    }
}
