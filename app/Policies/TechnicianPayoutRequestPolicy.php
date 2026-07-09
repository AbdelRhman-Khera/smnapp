<?php

namespace App\Policies;

use App\Models\TechnicianPayoutRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TechnicianPayoutRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_technician::payout::request');
    }

    public function view(User $user, TechnicianPayoutRequest $technicianPayoutRequest): bool
    {
        return $user->can('view_technician::payout::request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_technician::payout::request');
    }

    public function update(User $user, TechnicianPayoutRequest $technicianPayoutRequest): bool
    {
        return $user->can('update_technician::payout::request');
    }

    public function delete(User $user, TechnicianPayoutRequest $technicianPayoutRequest): bool
    {
        return $user->can('delete_technician::payout::request');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_technician::payout::request');
    }
}
