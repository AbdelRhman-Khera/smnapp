<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        return response()->json([
            'status' => 200,
            'message' => __('messages.notifications_fetched'),
            'data' => Auth::user()->notifications,
        ]);
    }
    public function getUnreadNotifications(Request $request)
    {
        return response()->json([
            'status' => 200,
            'message' => __('messages.notifications_fetched'),
            'data' => Auth::user()->unreadNotifications,
        ]);
    }


    public function markNotificationAsRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json([
                'status' => 200,
                'message' => __('messages.notification_read'),
            ]);
        }

        return response()->json([
            'status' => 404,
            'message' => __('messages.notification_not_found'),
        ], 404);
    }


    public function markAllNotificationsAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json([
            'status' => 200,
            'message' => __('messages.all_notifications_read'),
        ]);
    }
}
