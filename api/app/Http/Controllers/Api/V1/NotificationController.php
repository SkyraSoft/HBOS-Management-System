<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user's business.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->business) {
            return response()->json(['data' => []]);
        }

        // We assume notifications are sent to the Business model
        // So we retrieve them from the user's business
        $business = $user->business;
        $notifications = $business->notifications()->latest()->get();

        return response()->json([
            'data' => $notifications
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->business) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification = $user->business->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->business) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->business->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * Dismiss (delete) a specific notification.
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->business) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification = $user->business->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
        }

        return response()->json(['message' => 'Notification dismissed.']);
    }
}
