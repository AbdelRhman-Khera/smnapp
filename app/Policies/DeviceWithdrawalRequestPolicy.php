<?php

namespace App\Policies;

use App\Models\DeviceWithdrawalRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeviceWithdrawalRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_device::withdrawal::request');
    }

    public function view(User $user, DeviceWithdrawalRequest $model): bool
    {
        return $user->can('view_device::withdrawal::request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_device::withdrawal::request');
    }

    public function update(User $user, DeviceWithdrawalRequest $model): bool
    {
        return $user->can('update_device::withdrawal::request');
    }

    public function delete(User $user, DeviceWithdrawalRequest $model): bool
    {
        return $user->can('delete_device::withdrawal::request');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_device::withdrawal::request');
    }
}
