<?php

namespace App\Services;

use App\Models\CashRegisterSession;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashRegisterService
{
    protected AccountMovementService $movementService;

    public function __construct(AccountMovementService $movementService)
    {
        $this->movementService = $movementService;
    }

    /**
     * Resolve default or specific active cash drawer account for branch with tenant isolation.
     */
    public function resolveDrawer(int $businessId, int $branchId, ?int $drawerId = null): FinancialAccount
    {
        $query = FinancialAccount::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('type', 'cash')
            ->where('status', 'active');

        if ($drawerId) {
            $drawer = $query->find($drawerId);
            if (!$drawer) {
                throw ValidationException::withMessages([
                    'financial_account_id' => ['Specified cash drawer is invalid, inactive, or belongs to another branch/business.'],
                ]);
            }
            return $drawer;
        }

        $drawer = $query->orderByDesc('is_default')->orderBy('id', 'asc')->first();
        if (!$drawer) {
            throw ValidationException::withMessages([
                'branch_id' => ['No active cash drawer account configured for this branch.'],
            ]);
        }

        return $drawer;
    }

    /**
     * Retrieve active open session for branch and drawer.
     */
    public function getCurrentSession(int $businessId, int $branchId, ?int $drawerId = null): ?CashRegisterSession
    {
        $drawer = $this->resolveDrawer($businessId, $branchId, $drawerId);

        return CashRegisterSession::with(['openedBy', 'closedBy', 'financialAccount', 'branch'])
            ->where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('financial_account_id', $drawer->id)
            ->where('status', 'open')
            ->first();
    }

    /**
     * Open a cash register session with drawer row-lock and opening float validation.
     */
    public function openRegisterSession(
        User $user,
        int $businessId,
        int $branchId,
        float|string $openingFloat,
        ?int $drawerId = null,
        ?string $notes = null
    ): CashRegisterSession {
        $openingFloatStr = bcadd((string) $openingFloat, '0.00', 2);
        if (bccomp($openingFloatStr, '0.00', 2) < 0) {
            throw ValidationException::withMessages([
                'opening_float' => ['Opening float cannot be negative.'],
            ]);
        }

        return DB::transaction(function () use ($user, $businessId, $branchId, $openingFloatStr, $drawerId, $notes) {
            $drawer = $this->resolveDrawer($businessId, $branchId, $drawerId);

            // Lock drawer FinancialAccount row first to serialize opening and close operations
            $lockedDrawer = FinancialAccount::where('id', $drawer->id)->lockForUpdate()->first();

            // Check if active session already exists for this drawer
            $existingSession = CashRegisterSession::where('financial_account_id', $lockedDrawer->id)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if ($existingSession) {
                throw ValidationException::withMessages([
                    'session' => ['An open register session already exists for this cash drawer.'],
                ]);
            }

            // Verify physical float equals registered drawer balance (Zero Ledger Drift)
            $currentBalance = $this->movementService->calculateBalance($lockedDrawer);
            if (bccomp($openingFloatStr, $currentBalance, 2) !== 0) {
                $isOwnerOrManager = $user->hasRole('Business Owner') || $user->hasRole('Branch Manager');
                $hasNoMovements = AccountMovement::where('account_id', $lockedDrawer->id)->where('status', 'posted')->doesntExist();
                $hasZeroOpening = bccomp((string) ($lockedDrawer->opening_balance ?? '0.00'), '0.00', 2) === 0;

                if ($isOwnerOrManager && $hasNoMovements && $hasZeroOpening && bccomp($openingFloatStr, '0.00', 2) > 0) {
                    AccountMovement::create([
                        'business_id' => $businessId,
                        'branch_id' => $branchId,
                        'account_id' => $lockedDrawer->id,
                        'type' => 'inflow',
                        'movement_category' => 'capital_in',
                        'reference_type' => CashRegisterSession::class,
                        'reference_id' => $lockedDrawer->id,
                        'amount' => $openingFloatStr,
                        'date' => now()->toDateString(),
                        'description' => "Initial cash drawer opening float / capital funding for drawer #{$lockedDrawer->id}",
                        'user_id' => $user->id,
                        'status' => 'posted',
                        'created_at' => now()->subSecond(),
                    ]);

                    $currentBalance = $this->movementService->recalculateBalance($lockedDrawer);

                    app(\App\Services\AuditService::class)->log(
                        logName: 'cash_register',
                        event: 'opening_float_initialized',
                        description: "Initial cash drawer opening float of {$openingFloatStr} established for drawer #{$lockedDrawer->id}",
                        subject: $lockedDrawer,
                        properties: [
                            'drawer_id' => $lockedDrawer->id,
                            'opening_float' => $openingFloatStr,
                            'user_id' => $user->id,
                        ],
                        branchId: $branchId,
                        businessId: $businessId
                    );
                } else {
                    if (!$isOwnerOrManager) {
                        throw ValidationException::withMessages([
                            'opening_float' => ['Opening cash does not match the registered drawer balance. Please ask the Business Owner or Branch Manager to reconcile it.'],
                        ]);
                    } else {
                        throw ValidationException::withMessages([
                            'opening_float' => ["Opening cash ({$openingFloatStr}) does not match current drawer balance ({$currentBalance}). Please record a capital injection, transfer, or balance adjustment first."],
                        ]);
                    }
                }
            }

            $session = CashRegisterSession::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'financial_account_id' => $lockedDrawer->id,
                'user_id' => $user->id,
                'opening_float' => $openingFloatStr,
                'opened_at' => now(),
                'status' => 'open',
                'notes' => $notes,
            ]);

            app(\App\Services\AuditService::class)->log(
                logName: 'cash_register',
                event: 'opened',
                description: "Cash register session #{$session->id} opened with float {$openingFloatStr}",
                subject: $session,
                properties: [
                    'action' => 'open_session',
                    'session_id' => $session->id,
                    'opening_float' => $openingFloatStr,
                    'financial_account_id' => $lockedDrawer->id,
                ],
                branchId: $branchId,
                businessId: $businessId
            );

            return $session->load(['openedBy', 'financialAccount', 'branch']);
        });
    }

    /**
     * Calculate expected cash strictly from account_movements during session interval.
     * Expected = opening_float + SUM(posted inflows) - SUM(posted outflows)
     */
    /**
     * Calculate expected cash strictly from account_movements during session interval.
     * Expected = opening_float + SUM(posted inflows) - SUM(posted outflows)
     * Enforces deterministic same-second boundary separation and zero reconciliation self-inclusion.
     */
    public function calculateExpectedCash(CashRegisterSession $session, ?Carbon $cutoff = null): string
    {
        $cutoffAt = $cutoff ?? ($session->status === 'closed' ? $session->closed_at : now());

        // Check if there was a previous closed session for this drawer whose closed_at >= opened_at
        // If so, use strict '>' for opened_at to eliminate same-second double attribution.
        $hasPreviousSameSecondClose = CashRegisterSession::where('financial_account_id', $session->financial_account_id)
            ->where('id', '!=', $session->id)
            ->where('status', 'closed')
            ->where('closed_at', '>=', $session->opened_at)
            ->exists();

        $startOp = $hasPreviousSameSecondClose ? '>' : '>=';

        // Query movements within session interval, excluding day-close variance adjustments (Reconciliation Self-Inclusion: 0)
        $inflows = AccountMovement::where('account_id', $session->financial_account_id)
            ->where('status', 'posted')
            ->where('type', 'inflow')
            ->where('created_at', $startOp, $session->opened_at)
            ->where('created_at', '<=', $cutoffAt)
            ->where(function ($q) {
                $q->whereNull('reference_type')
                  ->orWhere('reference_type', '!=', CashRegisterSession::class);
            })
            ->sum('amount');

        $outflows = AccountMovement::where('account_id', $session->financial_account_id)
            ->where('status', 'posted')
            ->where('type', 'outflow')
            ->where('created_at', $startOp, $session->opened_at)
            ->where('created_at', '<=', $cutoffAt)
            ->where(function ($q) {
                $q->whereNull('reference_type')
                  ->orWhere('reference_type', '!=', CashRegisterSession::class);
            })
            ->sum('amount');

        $inflowsStr = (string) ($inflows ?? '0.00');
        $outflowsStr = (string) ($outflows ?? '0.00');
        $netMovements = bcsub($inflowsStr, $outflowsStr, 2);

        return bcadd((string) $session->opening_float, $netMovements, 2);
    }

    /**
     * Close register session with strict drawer lock, immutable snapshot, and variance reconciliation.
     */
    public function closeRegisterSession(
        User $user,
        CashRegisterSession $session,
        float|string $countedCash,
        ?string $varianceReason = null,
        ?string $notes = null
    ): CashRegisterSession {
        $countedCashStr = bcadd((string) $countedCash, '0.00', 2);
        if (bccomp($countedCashStr, '0.00', 2) < 0) {
            throw ValidationException::withMessages([
                'closing_cash_counted' => ['Counted cash cannot be negative.'],
            ]);
        }

        return DB::transaction(function () use ($user, $session, $countedCashStr, $varianceReason, $notes) {
            // 1. Lock drawer FinancialAccount row first
            $lockedDrawer = FinancialAccount::where('id', $session->financial_account_id)->lockForUpdate()->first();
            if (!$lockedDrawer) {
                throw ValidationException::withMessages([
                    'session' => ['Associated financial account not found.'],
                ]);
            }

            // 2. Lock CashRegisterSession row second
            $lockedSession = CashRegisterSession::where('id', $session->id)->lockForUpdate()->first();
            if (!$lockedSession) {
                throw ValidationException::withMessages([
                    'session' => ['Session not found.'],
                ]);
            }

            if ($lockedSession->status !== 'open') {
                throw ValidationException::withMessages([
                    'session' => ['This register session is already closed.'],
                ]);
            }

            // 3. Establish immutable close cutoff timestamp
            $closeCutoffAt = now();

            // 4. Calculate expected cash from ledger movements
            $expectedCash = $this->calculateExpectedCash($lockedSession, $closeCutoffAt);

            // 5. Calculate variance: Counted - Expected
            $variance = bcsub($countedCashStr, $expectedCash, 2);

            // 6. Enforce variance reason if discrepancy exists
            if (bccomp($variance, '0.00', 2) !== 0 && empty(trim($varianceReason ?? ''))) {
                $isOwnerOrManager = $user->hasRole('Business Owner') || $user->hasRole('Branch Manager');
                if (!$isOwnerOrManager) {
                    // Blind Cashier Privacy: do not reveal expected cash, variance, or overage/shortage direction
                    throw ValidationException::withMessages([
                        'variance_reason' => ['Variance reason is required to close the cash drawer.'],
                        'reason_required' => [true],
                    ]);
                } else {
                    throw ValidationException::withMessages([
                        'variance_reason' => ["Variance reason is required. Counted: {$countedCashStr}, Expected: {$expectedCash}, Variance: {$variance}."],
                        'reason_required' => [true],
                    ]);
                }
            }

            // 7. Store closing snapshot using PRE-ADJUSTMENT expected cash and variance
            $lockedSession->closing_cash_expected = $expectedCash;
            $lockedSession->closing_cash_counted = $countedCashStr;
            $lockedSession->cash_variance = $variance;
            $lockedSession->variance_reason = $varianceReason;
            $lockedSession->closed_by_user_id = $user->id;
            $lockedSession->closed_at = $closeCutoffAt;
            $lockedSession->status = 'closed';
            if ($notes !== null) {
                $lockedSession->notes = $notes;
            }
            $lockedSession->save();

            // 8. Reconcile variance with audited treasury adjustment movement if variance != 0
            if (bccomp($variance, '0.00', 2) !== 0) {
                if (bccomp($variance, '0.00', 2) < 0) {
                    // Shortage: outflow adjustment equal to abs(variance)
                    $adjustmentAmount = bcmul($variance, '-1', 2);
                    $adjMovement = $this->movementService->postOutflow($lockedDrawer, [
                        'branch_id' => $lockedSession->branch_id,
                        'movement_category' => 'adjustment',
                        'reference_type' => CashRegisterSession::class,
                        'reference_id' => $lockedSession->id,
                        'amount' => $adjustmentAmount,
                        'date' => $closeCutoffAt->toDateString(),
                        'description' => "Day-close cash discrepancy reconciliation (shortage) for Session #{$lockedSession->id}",
                        'user_id' => $user->id,
                        'idempotency_key' => "crs_adj_out_{$lockedSession->id}",
                    ]);
                } else {
                    // Overage: inflow adjustment equal to variance
                    $adjMovement = $this->movementService->postInflow($lockedDrawer, [
                        'branch_id' => $lockedSession->branch_id,
                        'movement_category' => 'adjustment',
                        'reference_type' => CashRegisterSession::class,
                        'reference_id' => $lockedSession->id,
                        'amount' => $variance,
                        'date' => $closeCutoffAt->toDateString(),
                        'description' => "Day-close cash discrepancy reconciliation (overage) for Session #{$lockedSession->id}",
                        'user_id' => $user->id,
                        'idempotency_key' => "crs_adj_in_{$lockedSession->id}",
                    ]);
                }

                $lockedSession->reconciliation_movement_id = $adjMovement->id;
                $lockedSession->save();
            }

            // 9. Audit trail
            app(\App\Services\AuditService::class)->log(
                logName: 'cash_register',
                event: 'closed',
                description: "Cash register session #{$lockedSession->id} closed. Counted: {$countedCashStr}, Expected: {$expectedCash}, Variance: {$variance}",
                subject: $lockedSession,
                properties: [
                    'action' => 'close_session',
                    'session_id' => $lockedSession->id,
                    'opening_float' => $lockedSession->opening_float,
                    'closing_cash_expected' => $expectedCash,
                    'closing_cash_counted' => $countedCashStr,
                    'cash_variance' => $variance,
                    'variance_reason' => $varianceReason,
                    'reconciliation_movement_id' => $lockedSession->reconciliation_movement_id,
                ],
                branchId: $lockedSession->branch_id,
                businessId: $lockedSession->business_id
            );

            return $lockedSession->fresh(['openedBy', 'closedBy', 'financialAccount', 'branch', 'reconciliationMovement']);
        });
    }

    /**
     * Daily summary of sessions for reporting.
     */
    public function getDailySummary(int $businessId, ?int $branchId = null, ?string $date = null): array
    {
        $targetDate = $date ?? Carbon::now('Asia/Karachi')->toDateString();

        $query = CashRegisterSession::with(['openedBy', 'closedBy', 'financialAccount', 'branch'])
            ->where('business_id', $businessId)
            ->whereDate('opened_at', $targetDate);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $sessions = $query->orderBy('opened_at', 'asc')->get();

        $totalOpeningFloat = '0.00';
        $totalExpected = '0.00';
        $totalCounted = '0.00';
        $totalVariance = '0.00';

        foreach ($sessions as $s) {
            $totalOpeningFloat = bcadd($totalOpeningFloat, (string) ($s->opening_float ?? '0.00'), 2);
            $totalExpected = bcadd($totalExpected, (string) ($s->closing_cash_expected ?? '0.00'), 2);
            $totalCounted = bcadd($totalCounted, (string) ($s->closing_cash_counted ?? '0.00'), 2);
            $totalVariance = bcadd($totalVariance, (string) ($s->cash_variance ?? '0.00'), 2);
        }

        return [
            'date' => $targetDate,
            'session_count' => $sessions->count(),
            'total_opening_float' => (float) $totalOpeningFloat,
            'total_closing_cash_expected' => (float) $totalExpected,
            'total_closing_cash_counted' => (float) $totalCounted,
            'total_cash_variance' => (float) $totalVariance,
            'sessions' => $sessions,
        ];
    }
}
