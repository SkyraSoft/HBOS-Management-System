<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\KhataTransaction;
use App\Models\Sale;
use App\Services\CustomerAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    protected CustomerAccountService $accountService;

    public function __construct(CustomerAccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    protected function getBusinessId(Request $request): int
    {
        return ResolveActiveBusiness::requireActiveBusinessId();
    }

    public function index(Request $request)
    {
        $businessId = $this->getBusinessId($request);
        $customers = Customer::where('business_id', $businessId)->get();

        $user = $request->user();
        $isManager = $user->hasRole('Branch Manager') && !$user->hasRole('Business Owner');
        $managerBranch = $isManager ? $user->branches()->first() : null;

        $data = $customers->map(function ($c) use ($isManager, $managerBranch) {
            $raw = $this->accountService->calculateRawBalance($c);
            $outstanding = max(0.00, $raw);
            $credit = max(0.00, -$raw);

            $item = $c->toArray();
            $item['outstanding'] = $outstanding;
            $item['credit'] = $credit;

            if ($isManager && $managerBranch) {
                $item['branch_outstanding'] = $this->accountService->calculateBranchExposure($c, $managerBranch);
            } else {
                $item['branch_outstanding'] = $outstanding;
            }

            return $item;
        });

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $businessId = $this->getBusinessId($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $openingBalance = round((float) ($request->opening_balance ?? 0.00), 2);

        $customer = Customer::create([
            'business_id' => $businessId,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'opening_balance' => $openingBalance,
        ]);

        $this->accountService->recalculateBalance($customer);

        return response()->json($customer, 201);
    }

    public function show(Request $request, string $id)
    {
        $businessId = $this->getBusinessId($request);
        $customer = Customer::where('business_id', $businessId)->findOrFail($id);

        $raw = $this->accountService->calculateRawBalance($customer);
        $data = $customer->toArray();
        $data['outstanding'] = max(0.00, $raw);
        $data['credit'] = max(0.00, -$raw);

        $user = $request->user();
        if ($user->hasRole('Branch Manager') && !$user->hasRole('Business Owner')) {
            $branch = $user->branches()->first();
            if ($branch) {
                $data['branch_outstanding'] = $this->accountService->calculateBranchExposure($customer, $branch);
            }
        }

        return response()->json($data);
    }

    public function update(Request $request, string $id)
    {
        $businessId = $this->getBusinessId($request);
        $customer = Customer::where('business_id', $businessId)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        // Explicitly guard balance and opening_balance from client modification
        $customer->update($request->only(['name', 'phone', 'email', 'address']));

        return response()->json($customer);
    }

    public function destroy(Request $request, string $id)
    {
        $businessId = $this->getBusinessId($request);
        $customer = Customer::where('business_id', $businessId)->findOrFail($id);

        // Soft-delete to preserve historical ledger integrity
        $customer->delete();

        return response()->json(['message' => 'Customer deactivated']);
    }

    public function ledger(Request $request, string $id)
    {
        $businessId = $this->getBusinessId($request);
        $customer = Customer::where('business_id', $businessId)->findOrFail($id);

        $ledger = $this->accountService->getAccountLedger($customer);
        $raw = $this->accountService->calculateRawBalance($customer);

        return response()->json([
            'customer' => $customer,
            'outstanding' => max(0.00, $raw),
            'credit' => max(0.00, -$raw),
            'ledger' => $ledger,
        ]);
    }
}
