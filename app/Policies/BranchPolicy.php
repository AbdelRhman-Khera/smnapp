<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_branch');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->can('view_branch');
    }

    public function create(User $user): bool
    {
        return $user->can('create_branch');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('update_branch');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->can('delete_branch');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_branch');
    }
}
