<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Services\CustomerPaymentService;
use Illuminate\Http\Request;

class CustomerPaymentController extends Controller
{
    protected CustomerPaymentService $paymentService;

    public function __construct(CustomerPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    protected function getBusinessId(Request $request): int
    {
        return ResolveActiveBusiness::requireActiveBusinessId();
    }

    public function index(Request $request, string $customerId)
    {
        $businessId = $this->getBusinessId($request);
        $customer = Customer::where('business_id', $businessId)->findOrFail($customerId);

        $payments = CustomerPayment::with(['branch', 'sale', 'user', 'reversedBy'])
            ->where('business_id', $businessId)
            ->where('customer_id', $customer->id)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($payments);
    }

    public function store(Request $request, string $customerId)
    {
        $businessId = $this->getBusinessId($request);
        $customer = Customer::where('business_id', $businessId)->findOrFail($customerId);

        $request->validate([
            'branch_id' => 'required|integer',
            'sale_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        $payload = $request->all();
        $payload['customer_id'] = $customer->id;
        $payload['business_id'] = $businessId;

        $payment = $this->paymentService->recordPayment($payload, $request->user());

        app(\App\Services\AuditService::class)->log(
            logName: 'customer',
            event: 'created',
            description: "Customer payment of {$payment->amount} recorded for {$customer->name}",
            subject: $payment,
            properties: [
                'action' => 'customer_payment',
                'payment_id' => $payment->id,
                'customer_id' => $customer->id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
            ],
            branchId: $payment->branch_id,
            businessId: $businessId
        );

        return response()->json($payment->load(['customer', 'branch', 'sale', 'user']), 201);
    }

    public function reverse(Request $request, string $paymentId)
    {
        $businessId = $this->getBusinessId($request);
        $payment = CustomerPayment::where('business_id', $businessId)->findOrFail($paymentId);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $reversed = $this->paymentService->reversePayment($payment, $request->user(), $request->reason);

        app(\App\Services\AuditService::class)->log(
            logName: 'customer',
            event: 'reversed',
            description: "Customer payment #{$payment->id} reversed. Reason: {$request->reason}",
            subject: $reversed,
            properties: [
                'action' => 'customer_payment',
                'payment_id' => $payment->id,
                'customer_id' => $payment->customer_id,
                'amount' => (float) $payment->amount,
                'reason' => $request->reason,
            ],
            branchId: $payment->branch_id,
            businessId: $businessId
        );

        return response()->json($reversed->load(['customer', 'branch', 'sale', 'user', 'reversedBy']));
    }
}
