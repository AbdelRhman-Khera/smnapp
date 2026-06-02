<?php

namespace App\Policies;

use App\Models\TechnicianSparePartRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TechnicianSparePartRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_technician::spare::part::request');
    }

    public function view(User $user, TechnicianSparePartRequest $technicianSparePartRequest): bool
    {
        return $user->can('view_technician::spare::part::request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_technician::spare::part::request');
    }

    public function update(User $user, TechnicianSparePartRequest $technicianSparePartRequest): bool
    {
        return $user->can('update_technician::spare::part::request');
    }

    public function delete(User $user, TechnicianSparePartRequest $technicianSparePartRequest): bool
    {
        return $user->can('delete_technician::spare::part::request');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_technician::spare::part::request');
    }
}
