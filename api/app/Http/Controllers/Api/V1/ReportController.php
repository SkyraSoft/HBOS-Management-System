<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\DashboardService;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    protected ReportService $reportService;
    protected DashboardService $dashboardService;

    public function __construct(ReportService $reportService, DashboardService $dashboardService)
    {
        $this->reportService = $reportService;
        $this->dashboardService = $dashboardService;
    }

    protected function getActiveBusinessId(Request $request): int
    {
        if (app()->has('active_business_id') && app('active_business_id')) {
            return (int) app('active_business_id');
        }
        abort(400, 'Active business context is missing.');
    }

    protected function resolveBranchScope(Request $request, int $businessId): ?int
    {
        /** @var User $user */
        $user = $request->user();
        $isOwner = $user->hasRole('Business Owner');
        $isManager = $user->hasRole('Branch Manager');
        $isSalesperson = $user->hasRole('Salesperson');

        if ($isSalesperson && !$isOwner) {
            abort(403, 'Salespersons are not authorized to view executive reports.');
        }

        $inputBranch = $request->query('branch_id');

        if ($isManager && !$isOwner) {
            if (empty($user->branch_id)) {
                abort(403, 'Branch Manager is not assigned to any branch.');
            }
            if ($inputBranch && strtolower(trim($inputBranch)) === 'all') {
                abort(403, 'Branch Managers cannot request All Branches reports.');
            }
            if ($inputBranch && (int) $inputBranch !== (int) $user->branch_id) {
                abort(403, 'Branch Managers cannot access reports for another branch.');
            }
            return (int) $user->branch_id;
        }

        if ($inputBranch && strtolower(trim($inputBranch)) !== 'all') {
            $bId = (int) $inputBranch;
            $branch = Branch::where('business_id', $businessId)->find($bId);
            if (!$branch) {
                throw ValidationException::withMessages([
                    'branch_id' => ['Selected branch does not belong to active business.'],
                ]);
            }
            return $bId;
        }

        return null;
    }

    public function sales(Request $request)
    {
        $businessId = $this->getActiveBusinessId($request);
        $branchId = $this->resolveBranchScope($request, $businessId);
        $dateRange = $this->dashboardService->resolveDateRange($request->all());
        $format = $request->query('export') ?? $request->query('format');

        return $this->reportService->getSalesReport($businessId, $branchId, $dateRange['from'], $dateRange['to'], $format);
    }

    public function inventory(Request $request)
    {
        $businessId = $this->getActiveBusinessId($request);
        $branchId = $this->resolveBranchScope($request, $businessId);
        $format = $request->query('export') ?? $request->query('format');

        return $this->reportService->getInventoryReport($businessId, $branchId, $format);
    }

    public function customers(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $businessId = $this->getActiveBusinessId($request);
        $branchId = $this->resolveBranchScope($request, $businessId);
        $branch = $branchId ? Branch::where('business_id', $businessId)->find($branchId) : null;
        $format = $request->query('export') ?? $request->query('format');

        return $this->reportService->getCustomerReport($businessId, $branch, $format);
    }

    public function suppliers(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user->hasRole('Business Owner')) {
            abort(403, 'Supplier financial reports are restricted to Business Owners.');
        }

        $businessId = $this->getActiveBusinessId($request);
        $format = $request->query('export') ?? $request->query('format');

        return $this->reportService->getSupplierReport($businessId, $format);
    }

    public function expenses(Request $request)
    {
        $businessId = $this->getActiveBusinessId($request);
        $branchId = $this->resolveBranchScope($request, $businessId);
        $dateRange = $this->dashboardService->resolveDateRange($request->all());
        $format = $request->query('export') ?? $request->query('format');

        return $this->reportService->getExpenseReport($businessId, $branchId, $dateRange['from'], $dateRange['to'], $format);
    }

    public function financialAccounts(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $businessId = $this->getActiveBusinessId($request);
        $branchId = $this->resolveBranchScope($request, $businessId);
        $dateRange = $this->dashboardService->resolveDateRange($request->all());
        $isOwner = $user->hasRole('Business Owner');
        $format = $request->query('export') ?? $request->query('format');

        return $this->reportService->getFinancialAccountReport($businessId, $branchId, $dateRange['from'], $dateRange['to'], $isOwner, $format);
    }

    public function branches(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user->hasRole('Business Owner')) {
            abort(403, 'Branch comparison report is restricted to Business Owners.');
        }

        $businessId = $this->getActiveBusinessId($request);
        $dateRange = $this->dashboardService->resolveDateRange($request->all());
        $format = $request->query('export') ?? $request->query('format');

        return $this->reportService->getBranchReport($businessId, $dateRange['from'], $dateRange['to'], $format);
    }
}
