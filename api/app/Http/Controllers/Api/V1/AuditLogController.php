<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a paginated listing of audit logs for the active business.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Role-based Authorization: Salesperson is strictly forbidden
        $isOwner = $user->hasRole('Business Owner');
        $isManager = $user->hasRole('Branch Manager');

        if (!$isOwner && !$isManager) {
            return response()->json([
                'message' => 'Unauthorized. Only Business Owners and Branch Managers can access audit logs.'
            ], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        // 2. Request Validation for filter parameters
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'branch_id' => 'nullable|integer',
            'causer_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'log_name' => 'nullable|string|max:100',
            'event' => 'nullable|string|max:100',
        ]);

        $query = ActivityLog::with([
            'causer:id,name,email',
            'branch:id,name,code'
        ])->where('business_id', $activeBusinessId);

        // 3. Branch Manager Scoping: Restricted to assigned branch & operational logs only
        if ($isManager && !$isOwner) {
            $userBranchId = $user->branch_id;
            if (!$userBranchId) {
                return response()->json([
                    'message' => 'Branch Manager must be assigned to a branch to view operational audit logs.'
                ], 403);
            }

            if ($request->filled('branch_id') && (int) $request->input('branch_id') !== (int) $userBranchId) {
                return response()->json([
                    'message' => 'Unauthorized. Branch Managers cannot access foreign branch audit logs.'
                ], 403);
            }

            // Exclude central security, business settings, and user governance administration
            $query->where('branch_id', $userBranchId)
                  ->whereNotIn('log_name', ['security', 'settings', 'governance']);
        }

        // 4. Filters
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        if ($isOwner && $request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', (int) $request->input('causer_id'));
        }

        if ($request->filled('log_name') && $request->input('log_name') !== 'All') {
            $query->where('log_name', strtolower($request->input('log_name')));
        }

        if ($request->filled('event') && $request->input('event') !== 'All') {
            $query->where('event', strtolower($request->input('event')));
        }

        if ($request->filled('search')) {
            $search = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', $search)
                  ->orWhere('event', 'like', $search)
                  ->orWhere('log_name', 'like', $search);
            });
        }

        // 4. Pagination & Ordering
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $logs = $query->orderBy('created_at', 'desc')
                      ->orderBy('id', 'desc')
                      ->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Display a specific audit log record.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $isOwner = $user->hasRole('Business Owner');
        $isManager = $user->hasRole('Branch Manager');

        if (!$isOwner && !$isManager) {
            return response()->json([
                'message' => 'Unauthorized. Only Business Owners and Branch Managers can access audit logs.'
            ], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $log = ActivityLog::with([
            'causer:id,name,email',
            'branch:id,name,code',
        ])->where('business_id', $activeBusinessId)
          ->where('id', $id)
          ->first();

        if (!$log) {
            return response()->json(['message' => 'Audit log not found.'], 404);
        }

        // Branch Manager check
        if ($isManager && !$isOwner) {
            if ($log->branch_id !== $user->branch_id || in_array($log->log_name, ['security', 'settings', 'governance'])) {
                return response()->json(['message' => 'Audit log not found.'], 404);
            }
        }

        return response()->json(['data' => $log]);
    }
}
