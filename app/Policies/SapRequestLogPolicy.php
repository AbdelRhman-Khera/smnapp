<?php

namespace App\Policies;

use App\Models\SapRequestLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SapRequestLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_sap::request::log');
    }

    public function view(User $user, SapRequestLog $sapRequestLog): bool
    {
        return $user->can('view_sap::request::log');
    }

    public function create(User $user): bool
    {
        return $user->can('create_sap::request::log');
    }

    public function update(User $user, SapRequestLog $sapRequestLog): bool
    {
        return $user->can('update_sap::request::log');
    }

    public function delete(User $user, SapRequestLog $sapRequestLog): bool
    {
        return $user->can('delete_sap::request::log');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_sap::request::log');
    }
}
