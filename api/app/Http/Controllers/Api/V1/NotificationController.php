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
     * Get all notifications for the active business context, integrating active Needs Attention alerts.
     */
    public function index(Request $request, \App\Services\NeedsAttentionService $needsAttentionService)
    {
        $business = $this->getActiveBusiness();
        $persisted = $business->notifications()->latest()->get()->map(function($n) {
            return [
                'id' => (string) $n->id,
                'data' => $n->data,
                'read_at' => $n->read_at ? $n->read_at->toIso8601String() : null,
                'created_at' => $n->created_at ? $n->created_at->toIso8601String() : now()->toIso8601String(),
            ];
        });

        // Evaluate active Needs Attention alerts (supplier payables, customer credit, low stock, out of stock)
        if ($request->boolean('include_alerts', false)) {
            $evaluatedAlerts = $needsAttentionService->evaluateAlerts($business->id, null, true);
            $alertNotifications = collect($evaluatedAlerts)->map(function($alert, $index) {
                $actionPayload = match($alert['type'] ?? '') {
                    'supplier_payable', 'pending_supplier_invoices' => '/suppliers',
                    'customer_receivable', 'khata' => '/khata',
                    'low_stock', 'out_of_stock' => '/inventory',
                    default => '/dashboard',
                };
                $actionLabel = match($alert['type'] ?? '') {
                    'supplier_payable', 'pending_supplier_invoices' => 'Review Invoices',
                    'customer_receivable', 'khata' => 'Open Khata',
                    'low_stock', 'out_of_stock' => 'Restock Products',
                    default => 'View Details',
                };

                return [
                    'id' => 'alert_' . ($alert['type'] ?? 'sys') . '_' . $index,
                    'data' => [
                        'title' => $alert['title'] ?? 'Attention Required',
                        'message' => $alert['message'] ?? '',
                        'type' => 'Alerts & Attention',
                        'severity' => $alert['severity'] ?? 'warning',
                        'action_type' => 'navigate',
                        'action_payload' => $actionPayload,
                        'action_label' => $actionLabel,
                        'icon' => ($alert['severity'] ?? '') === 'critical' ? 'bi-exclamation-octagon-fill' : 'bi-exclamation-triangle-fill',
                        'colorClass' => ($alert['severity'] ?? '') === 'critical' ? 'text-danger' : 'text-warning',
                        'bgClass' => ($alert['severity'] ?? '') === 'critical' ? 'bg-danger-subtle' : 'bg-warning-subtle',
                    ],
                    'read_at' => null,
                    'created_at' => now()->toIso8601String(),
                ];
            });

            $all = $alertNotifications->concat($persisted)->values();
        } else {
            $all = $persisted->values();
        }

        return response()->json([
            'data' => $all
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        if (str_starts_with((string) $id, 'alert_')) {
            return response()->json(['message' => 'Notification marked as read.']);
        }

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
        if (str_starts_with((string) $id, 'alert_')) {
            return response()->json(['message' => 'Notification dismissed.']);
        }

        $business = $this->getActiveBusiness();
        $notification = $business->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
        }

        return response()->json(['message' => 'Notification dismissed.']);
    }
}
