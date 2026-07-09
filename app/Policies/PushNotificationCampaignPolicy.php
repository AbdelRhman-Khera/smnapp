<?php

namespace App\Policies;

use App\Models\PushNotificationCampaign;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PushNotificationCampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_push::notification::campaign');
    }

    public function view(User $user, PushNotificationCampaign $campaign): bool
    {
        return $user->can('view_push::notification::campaign');
    }

    public function create(User $user): bool
    {
        return $user->can('create_push::notification::campaign');
    }

    public function update(User $user, PushNotificationCampaign $campaign): bool
    {
        return $user->can('update_push::notification::campaign');
    }

    public function delete(User $user, PushNotificationCampaign $campaign): bool
    {
        return $user->can('delete_push::notification::campaign');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_push::notification::campaign');
    }
}
