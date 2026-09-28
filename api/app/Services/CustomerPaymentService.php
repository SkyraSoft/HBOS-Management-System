<?php

namespace App\Services;

use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerPaymentService
{
    protected CustomerAccountService $accountService;

    public function __construct(CustomerAccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    /**
     * Record a new append-only CustomerPayment collection event.
     */
    public function recordPayment(array $data, User $actor): CustomerPayment
    {
        return DB::transaction(function () use ($data, $actor) {
            $businessId = ResolveActiveBusiness::getBusinessId() ?? $data['business_id'] ?? null;
            if (!$businessId) {
                throw ValidationException::withMessages(['business_id' => 'Active Business context required.']);
            }

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($businessId);
            }

            // 1. Structural row lock on Customer
            $customer = Customer::where('id', $data['customer_id'])
                ->where('business_id', $businessId)
                ->lockForUpdate()
                ->first();

            if (!$customer) {
                throw ValidationException::withMessages(['customer_id' => 'Customer not found for active business.']);
            }

            // 2. Validate Branch
            $branchId = $data['branch_id'] ?? null;
            $branch = Branch::where('id', $branchId)
                ->where('business_id', $businessId)
                ->first();

            if (!$branch) {
                throw ValidationException::withMessages(['branch_id' => 'Branch not found or does not belong to active business.']);
            }

            // 3. Optional Sale validation
            $saleId = $data['sale_id'] ?? null;
            if ($saleId) {
                $sale = Sale::where('id', $saleId)
                    ->where('business_id', $businessId)
                    ->where('customer_id', $customer->id)
                    ->where('status', '!=', 'cancelled')
                    ->first();

                if (!$sale) {
                    throw ValidationException::withMessages(['sale_id' => 'Invalid or cancelled Sale for this Customer.']);
                }
            }

            // 4. Validate Amount
            $amount = round((float) ($data['amount'] ?? 0.00), 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
            }

            // 5. Role Authorization & Maximum Payment Limit
            $isOwner = $actor->hasRole('Business Owner');
            $isManager = $actor->hasRole('Branch Manager');

            if (!$isOwner && !$isManager) {
                throw ValidationException::withMessages(['authorization' => 'Salesperson is not authorized to record customer payments.']);
            }

            if ($isOwner) {
                $maxPayment = $this->accountService->calculateOutstanding($customer);
            } else { // Branch Manager
                // Check branch assignment
                $userBranchIds = $actor->branches()->pluck('branches.id')->toArray();
                if (!in_array($branch->id, $userBranchIds)) {
                    throw ValidationException::withMessages(['branch_id' => 'Manager is not assigned to this branch.']);
                }
                $maxPayment = $this->accountService->calculateBranchExposure($customer, $branch);
            }

            if ($maxPayment <= 0.00) {
                throw ValidationException::withMessages(['amount' => 'Customer has no outstanding balance to collect.']);
            }

            if ($amount > $maxPayment + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => sprintf('Payment amount %.2f exceeds authorized outstanding balance of %.2f.', $amount, $maxPayment)
                ]);
            }

            // 6. Idempotency Check
            $idempotencyKey = $data['idempotency_key'] ?? null;
            if ($idempotencyKey) {
                $existing = CustomerPayment::where('business_id', $businessId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    // Compare material payload
                    $sameCustomer = (int) $existing->customer_id === (int) $customer->id;
                    $sameBranch = (int) $existing->branch_id === (int) $branch->id;
                    $sameSale = (int) $existing->sale_id === (int) $saleId;
                    $sameAmount = abs((float) $existing->amount - $amount) < 0.001;
                    $sameMethod = $existing->payment_method === ($data['payment_method'] ?? 'cash');
                    
                    $reqDate = isset($data['date']) ? \Carbon\Carbon::parse($data['date'])->toDateString() : date('Y-m-d');
                    $existingDate = $existing->date ? \Carbon\Carbon::parse($existing->date)->toDateString() : null;
                    $sameDate = ($existingDate === $reqDate);

                    if ($sameCustomer && $sameBranch && $sameSale && $sameAmount && $sameMethod && $sameDate) {
                        return $existing; // Return original payment idempotently
                    }

                    throw ValidationException::withMessages([
                        'idempotency_key' => 'Conflicting payment payload for duplicate idempotency key.'
                    ]);
                }
            }

            // 7. Resolve Financial Account for payment settlement
            $targetAccount = null;
            if (!empty($data['financial_account_id'])) {
                $targetAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                    ->where('id', $data['financial_account_id'])
                    ->where('status', 'active')
                    ->first();
                if (!$targetAccount) {
                    throw ValidationException::withMessages([
                        'financial_account_id' => ['Selected financial account is invalid or inactive.']
                    ]);
                }
            } else {
                $isCash = strtolower(trim($data['payment_method'] ?? 'cash')) === 'cash';
                if ($isCash) {
                    $targetAccount = app(\App\Services\FinancialAccountService::class)->getDefaultCashAccount($businessId, $branch->id);
                } else {
                    $targetAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                        ->where('type', 'bank')
                        ->where('status', 'active')
                        ->first();
                }
            }

            if (!$targetAccount || $targetAccount->status !== 'active') {
                throw ValidationException::withMessages([
                    'financial_account_id' => ['No active financial account available to receive customer payment.']
                ]);
            }

            // 8. Generate tenant-scoped payment_number
            $paymentNumber = $this->generatePaymentNumber($businessId);

            // 9. Create CustomerPayment
            $payment = CustomerPayment::create([
                'business_id' => $businessId,
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'sale_id' => $saleId,
                'user_id' => $actor->id,
                'payment_number' => $paymentNumber,
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'date' => $data['date'] ?? date('Y-m-d'),
                'notes' => $data['notes'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'status' => 'posted',
            ]);

            // Post inflow account_movement
            app(\App\Services\AccountMovementService::class)->postInflow($targetAccount, [
                'branch_id' => $branch->id,
                'movement_category' => 'customer_payment',
                'reference_type' => CustomerPayment::class,
                'reference_id' => $payment->id,
                'amount' => $amount,
                'date' => $payment->date,
                'description' => "Customer Payment #{$paymentNumber}",
                'user_id' => $actor->id,
                'idempotency_key' => $idempotencyKey ? "cp_{$idempotencyKey}" : null,
            ]);

            // 10. Atomic balance recalculation
            $this->accountService->recalculateBalance($customer);

            return $payment;
        });
    }

    /**
     * Reverse / void a posted CustomerPayment.
     */
    public function reversePayment(CustomerPayment $payment, User $actor, string $reason): CustomerPayment
    {
        return DB::transaction(function () use ($payment, $actor, $reason) {
            $businessId = ResolveActiveBusiness::getActiveBusinessId();
            if (!$businessId) {
                if ($actor->businesses()->count() === 1) {
                    $businessId = (int) $actor->businesses()->value('businesses.id');
                } else {
                    $businessId = ResolveActiveBusiness::requireActiveBusinessId();
                }
            }
            if ((int) $payment->business_id !== (int) $businessId) {
                throw ValidationException::withMessages(['payment' => 'Unauthorized cross-tenant payment reversal.']);
            }

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($businessId);
            }

            $isOwner = $actor->hasRole('Business Owner');
            $isManager = $actor->hasRole('Branch Manager');

            if (!$isOwner && !$isManager) {
                throw ValidationException::withMessages([
                    'authorization' => 'Salesperson is not authorized to reverse customer payments.'
                ]);
            }

            if ($isManager && !$isOwner) {
                $userBranchIds = $actor->branches()->pluck('branches.id')->toArray();
                if (!in_array($payment->branch_id, $userBranchIds)) {
                    throw ValidationException::withMessages([
                        'authorization' => 'Manager is not authorized to reverse payments for unassigned branches.'
                    ]);
                }
            }

            if ($payment->status === 'voided') {
                throw ValidationException::withMessages(['status' => 'Payment is already voided.']);
            }

            // Update status to voided
            $payment->update([
                'status' => 'voided',
                'reversed_at' => now(),
                'reversed_by' => $actor->id,
                'reversal_reason' => $reason,
            ]);

            // Void linked AccountMovement if exists
            $movement = \App\Models\AccountMovement::where('business_id', $businessId)
                ->where('reference_type', CustomerPayment::class)
                ->where('reference_id', $payment->id)
                ->where('movement_category', 'customer_payment')
                ->where('status', 'posted')
                ->first();

            if ($movement) {
                app(\App\Services\AccountMovementService::class)->voidMovement($movement, $actor->id, "CustomerPayment voided: {$reason}");
            }

            // Recalculate customer balance
            $customer = Customer::where('id', $payment->customer_id)
                ->where('business_id', $businessId)
                ->first();

            if ($customer) {
                $this->accountService->recalculateBalance($customer);
            }

            return $payment;
        });
    }

    /**
     * Generate unique tenant-scoped payment_number (CP-YYYYMMDD-XXXX).
     */
    protected function generatePaymentNumber(int $businessId): string
    {
        $prefix = 'CP-' . date('Ymd') . '-';
        for ($i = 0; $i < 10; $i++) {
            $candidate = $prefix . sprintf('%04d', rand(1, 9999));
            $exists = CustomerPayment::where('business_id', $businessId)
                ->where('payment_number', $candidate)
                ->exists();
            if (!$exists) {
                return $candidate;
            }
        }
        return $prefix . sprintf('%04d', time() % 10000);
    }
}
