<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Business;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected function getActiveBusiness(): Business
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        return Business::findOrFail($businessId);
    }

    /**
     * Get all notifications for the active business context.
     */
    public function index(Request $request)
    {
        $business = $this->getActiveBusiness();
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
        $business = $this->getActiveBusiness();
        $notification = $business->notifications()->where('id', $id)->first();
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
        $business = $this->getActiveBusiness();
        $business->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * Dismiss (delete) a specific notification.
     */
    public function destroy(Request $request, $id)
    {
        $business = $this->getActiveBusiness();
        $notification = $business->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
        }

        return response()->json(['message' => 'Notification dismissed.']);
    }
}
