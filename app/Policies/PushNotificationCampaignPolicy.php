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
        return $user->can('view_any_push_notification_campaign');
    }

    public function view(User $user, PushNotificationCampaign $campaign): bool
    {
        return $user->can('view_push_notification_campaign');
    }

    public function create(User $user): bool
    {
        return $user->can('create_push_notification_campaign');
    }

    public function update(User $user, PushNotificationCampaign $campaign): bool
    {
        return $user->can('update_push_notification_campaign');
    }

    public function delete(User $user, PushNotificationCampaign $campaign): bool
    {
        return $user->can('delete_push_notification_campaign');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_push_notification_campaign');
    }
}
