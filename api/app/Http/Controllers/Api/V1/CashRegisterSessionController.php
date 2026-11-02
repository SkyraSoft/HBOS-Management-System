<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CashRegisterSession;
use App\Models\Branch;
use App\Models\User;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CashRegisterSessionController extends Controller
{
    protected CashRegisterService $registerService;

    public function __construct(CashRegisterService $registerService)
    {
        $this->registerService = $registerService;
    }

    protected function getActiveBusinessId(Request $request): int
    {
        if (app()->has('active_business_id') && app('active_business_id')) {
            return (int) app('active_business_id');
        }
        abort(400, 'Active business context is missing.');
    }

    protected function resolveAuthorizedBranchId(Request $request, int $businessId): int
    {
        /** @var User $user */
        $user = $request->user();
        $isOwner = $user->hasRole('Business Owner');
        $isManager = $user->hasRole('Branch Manager');

        $inputBranch = $request->input('branch_id') ?? $request->query('branch_id');
        if ($inputBranch === 'null' || $inputBranch === 'undefined' || $inputBranch === '') {
            $inputBranch = null;
        }

        if (!$isOwner) {
            if (empty($user->branch_id)) {
                abort(403, 'User is not assigned to any branch.');
            }
            if ($inputBranch && (int) $inputBranch !== (int) $user->branch_id) {
                abort(403, 'Users cannot operate across unauthorized branches.');
            }
            return (int) $user->branch_id;
        }

        if ($inputBranch) {
            $branch = Branch::where('business_id', $businessId)->find($inputBranch);
            if (!$branch) {
                throw ValidationException::withMessages([
                    'branch_id' => ['Selected branch does not belong to active business.'],
                ]);
            }
            return (int) $branch->id;
        }

        // Default to first active branch for owner
        $defaultBranch = Branch::where('business_id', $businessId)->first();
        if (!$defaultBranch) {
            abort(400, 'No active branch configured for this business.');
        }

        return (int) $defaultBranch->id;
    }

    /**
     * Retrieve active open register session for branch.
     */
    public function currentSession(Request $request)
    {
        $businessId = $this->getActiveBusinessId($request);
        $branchId = $this->resolveAuthorizedBranchId($request, $businessId);
        $drawerId = $request->query('financial_account_id');

        $session = $this->registerService->getCurrentSession($businessId, $branchId, $drawerId ? (int) $drawerId : null);

        if (!$session) {
            return response()->json([
                'has_active_session' => false,
                'session' => null,
            ]);
        }

        /** @var User $user */
        $user = $request->user();
        $isCashier = !$user->hasRole('Business Owner') && !$user->hasRole('Branch Manager');

        if ($isCashier) {
            // Cashier privacy: omit expected cash and internal metrics
            return response()->json([
                'has_active_session' => true,
                'session' => [
                    'id' => $session->id,
                    'business_id' => $session->business_id,
                    'branch_id' => $session->branch_id,
                    'financial_account_id' => $session->financial_account_id,
                    'opened_at' => $session->opened_at,
                    'opening_float' => (float) $session->opening_float,
                    'status' => $session->status,
                ],
            ]);
        }

        return response()->json([
            'has_active_session' => true,
            'session' => $session,
        ]);
    }

    /**
     * Open a cash register session.
     */
    public function open(Request $request)
    {
        $request->validate([
            'opening_float' => 'required|numeric|min:0',
            'branch_id' => 'nullable|integer',
            'financial_account_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        $businessId = $this->getActiveBusinessId($request);
        $branchId = $this->resolveAuthorizedBranchId($request, $businessId);
        $drawerId = $request->input('financial_account_id');

        $session = $this->registerService->openRegisterSession(
            $request->user(),
            $businessId,
            $branchId,
            $request->input('opening_float'),
            $drawerId ? (int) $drawerId : null,
            $request->input('notes')
        );

        return response()->json([
            'message' => 'Cash register session opened successfully.',
            'session' => $session,
        ], 201);
    }

    /**
     * Close a cash register session.
     */
    public function close(Request $request)
    {
        $request->validate([
            'session_id' => 'nullable|integer',
            'closing_cash_counted' => 'required|numeric|min:0',
            'variance_reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        $businessId = $this->getActiveBusinessId($request);
        $sessionId = $request->input('session_id');

        if ($sessionId) {
            $session = CashRegisterSession::where('business_id', $businessId)->findOrFail($sessionId);
        } else {
            $branchId = $this->resolveAuthorizedBranchId($request, $businessId);
            $session = $this->registerService->getCurrentSession($businessId, $branchId);
            if (!$session) {
                throw ValidationException::withMessages([
                    'session' => ['No active open register session found to close.'],
                ]);
            }
        }

        // Branch authorization check
        $user = $request->user();
        $isOwner = $user->hasRole('Business Owner');
        if (!$isOwner && (int) $user->branch_id !== (int) $session->branch_id) {
            abort(403, 'Users cannot close sessions for unauthorized branches.');
        }

        $closedSession = $this->registerService->closeRegisterSession(
            $user,
            $session,
            $request->input('closing_cash_counted'),
            $request->input('variance_reason'),
            $request->input('notes')
        );

        $isCashier = !$user->hasRole('Business Owner') && !$user->hasRole('Branch Manager');
        if ($isCashier) {
            // Cashier blind count privacy: hide expected cash and variance amount
            return response()->json([
                'message' => 'Register session closed successfully.',
                'session' => [
                    'id' => $closedSession->id,
                    'status' => $closedSession->status,
                    'closed_at' => $closedSession->closed_at,
                    'closing_cash_counted' => (float) $closedSession->closing_cash_counted,
                    'variance_reason' => $closedSession->variance_reason,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Register session closed successfully.',
            'session' => $closedSession,
        ]);
    }

    /**
     * Daily summary of sessions for reporting.
     */
    public function dailySummary(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->hasRole('Branch Manager')) {
            abort(403, 'Cashiers are not authorized to view daily register summaries.');
        }

        $businessId = $this->getActiveBusinessId($request);
        $branchId = null;

        if ($user->hasRole('Branch Manager')) {
            $branchId = (int) $user->branch_id;
        } elseif ($request->query('branch_id')) {
            $inputBranch = $request->query('branch_id');
            if (strtolower(trim($inputBranch)) !== 'all') {
                $branchId = (int) $inputBranch;
            }
        }

        $date = $request->query('date');
        $summary = $this->registerService->getDailySummary($businessId, $branchId, $date);

        return response()->json($summary);
    }

    /**
     * List sessions.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->hasRole('Branch Manager')) {
            abort(403, 'Cashiers are not authorized to list all register sessions.');
        }

        $businessId = $this->getActiveBusinessId($request);
        $query = CashRegisterSession::with(['openedBy', 'closedBy', 'financialAccount', 'branch'])
            ->where('business_id', $businessId);

        if ($user->hasRole('Branch Manager')) {
            $query->where('branch_id', (int) $user->branch_id);
        } elseif ($request->query('branch_id') && strtolower(trim($request->query('branch_id'))) !== 'all') {
            $query->where('branch_id', (int) $request->query('branch_id'));
        }

        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->query('date')) {
            $query->whereDate('opened_at', $request->query('date'));
        }

        $sessions = $query->orderBy('opened_at', 'desc')->paginate(20);

        return response()->json($sessions);
    }

    /**
     * Show single session.
     */
    public function show(Request $request, string $id)
    {
        $businessId = $this->getActiveBusinessId($request);
        $session = CashRegisterSession::with(['openedBy', 'closedBy', 'financialAccount', 'branch', 'reconciliationMovement'])
            ->where('business_id', $businessId)
            ->findOrFail($id);

        $user = $request->user();
        if (!$user->hasRole('Business Owner') && (int) $user->branch_id !== (int) $session->branch_id) {
            abort(403, 'Unauthorized access to session in another branch.');
        }

        $isCashier = !$user->hasRole('Business Owner') && !$user->hasRole('Branch Manager');
        if ($isCashier) {
            return response()->json([
                'id' => $session->id,
                'status' => $session->status,
                'opened_at' => $session->opened_at,
                'closed_at' => $session->closed_at,
                'opening_float' => (float) $session->opening_float,
                'closing_cash_counted' => (float) $session->closing_cash_counted,
            ]);
        }

        return response()->json($session);
    }
}
