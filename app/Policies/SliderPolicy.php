<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Slider;
use Illuminate\Auth\Access\HandlesAuthorization;

class SliderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_slider');
    }
    public function view(User $user, Slider $slider): bool
    {
        return $user->can('view_slider');
    }
    public function create(User $user): bool
    {
        return $user->can('create_slider');
    }
    public function update(User $user, Slider $slider): bool
    {
        return $user->can('update_slider');
    }
    public function delete(User $user, Slider $slider): bool
    {
        return $user->can('delete_slider');
    }
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_slider');
    }
    public function forceDelete(User $user, Slider $slider): bool
    {
        return $user->can('force_delete_slider');
    }
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_slider');
    }
    public function restore(User $user, Slider $slider): bool
    {
        return $user->can('restore_slider');
    }
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_slider');
    }
    public function replicate(User $user, Slider $slider): bool
    {
        return $user->can('replicate_slider');
    }
    public function reorder(User $user): bool
    {
        return $user->can('reorder_slider');
    }
}
