<?php

namespace App\Policies;

use App\Models\ProductHandover;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductHandoverPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_product::handover');
    }

    public function view(User $user, ProductHandover $productHandover): bool
    {
        return $user->can('view_product::handover');
    }

    public function create(User $user): bool
    {
        return $user->can('create_product::handover');
    }

    public function update(User $user, ProductHandover $productHandover): bool
    {
        return $user->can('update_product::handover');
    }

    public function delete(User $user, ProductHandover $productHandover): bool
    {
        return $user->can('delete_product::handover');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_product::handover');
    }
}
