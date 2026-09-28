<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Resolve active business ID from request container or authenticated user.
     */
    protected function getActiveBusinessId(Request $request): int
    {
        if (app()->has('active_business_id') && app('active_business_id')) {
            return (int) app('active_business_id');
        }
        abort(400, 'Active business context is missing.');
    }

    /**
     * Role-aware Dashboard Overview endpoint.
     * GET /api/v1/dashboard
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $businessId = $this->getActiveBusinessId($request);
        $dateRange = $this->dashboardService->resolveDateRange($request->all());

        $requestedBranchInput = $request->query('branch_id');
        $isOwner = $user->hasRole('Business Owner');
        $isManager = $user->hasRole('Branch Manager');
        $isSalesperson = $user->hasRole('Salesperson');

        // If user has no specific role assigned default to Owner logic if owner or safe fallback
        if (!$isOwner && !$isManager && !$isSalesperson) {
            $isOwner = true;
        }

        if ($isOwner) {
            $branchId = null;
            if ($requestedBranchInput && strtolower(trim($requestedBranchInput)) !== 'all') {
                $bId = (int) $requestedBranchInput;
                $branch = Branch::where('business_id', $businessId)->find($bId);
                if (!$branch) {
                    throw ValidationException::withMessages([
                        'branch_id' => ['Selected branch does not belong to active business.'],
                    ]);
                }
                $branchId = $bId;
            }
            $payload = $this->dashboardService->getOwnerDashboard($businessId, $branchId, $dateRange);
            return response()->json($payload);
        }

        if ($isManager) {
            if (empty($user->branch_id)) {
                abort(403, 'Branch Manager is not assigned to any branch.');
            }
            if ($requestedBranchInput && strtolower(trim($requestedBranchInput)) === 'all') {
                abort(403, 'Branch Managers are not authorized to view all branches.');
            }
            if ($requestedBranchInput && (int) $requestedBranchInput !== (int) $user->branch_id) {
                abort(403, 'Branch Managers are not authorized to view another branch.');
            }
            $payload = $this->dashboardService->getManagerDashboard($businessId, (int) $user->branch_id, $dateRange, $user);
            return response()->json($payload);
        }

        if ($isSalesperson) {
            if (empty($user->branch_id)) {
                abort(403, 'Salesperson is not assigned to any branch.');
            }
            if ($requestedBranchInput && (int) $requestedBranchInput !== (int) $user->branch_id) {
                abort(403, 'Salesperson cannot access metrics for another branch.');
            }
            $payload = $this->dashboardService->getSalespersonDashboard($businessId, (int) $user->branch_id, $dateRange, $user);
            return response()->json($payload);
        }

        abort(403, 'Unauthorized dashboard access.');
    }

    /**
     * Refactored legacy stats endpoint (backward compatibility delegating to secure service).
     * GET /api/v1/dashboard/stats
     */
    public function stats(Request $request)
    {
        return $this->index($request);
    }
}
