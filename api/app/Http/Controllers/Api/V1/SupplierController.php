<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Exception;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasPermissionTo('view suppliers')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $suppliers = Supplier::where('business_id', $businessId)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($suppliers);
    }

    public function store(StoreSupplierRequest $request)
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();

        $code = $request->code;
        if (empty($code)) {
            $latest = Supplier::where('business_id', $businessId)->latest('id')->first();
            $nextNum = $latest ? ($latest->id + 1) : 1;
            $code = 'SUP-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        } else {
            // Check code uniqueness per tenant
            $exists = Supplier::where('business_id', $businessId)->where('code', $code)->exists();
            if ($exists) {
                return response()->json(['error' => 'Supplier code already exists for this business.'], 422);
            }
        }

        $initialBalance = round((float) ($request->opening_balance ?? $request->balance ?? 0), 2);

        $supplier = Supplier::create([
            'business_id' => $businessId,
            'name' => $request->name,
            'code' => $code,
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'opening_balance' => $initialBalance,
            'balance' => $initialBalance
        ]);

        return response()->json($supplier, 201);
    }

    public function show(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('view suppliers')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $supplier = Supplier::where('business_id', $businessId)->findOrFail($id);
        return response()->json($supplier);
    }

    public function update(UpdateSupplierRequest $request, string $id)
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $supplier = Supplier::where('business_id', $businessId)->findOrFail($id);

        if (!empty($request->code) && $request->code !== $supplier->code) {
            $exists = Supplier::where('business_id', $businessId)
                ->where('code', $request->code)
                ->where('id', '!=', $supplier->id)
                ->exists();
            if ($exists) {
                return response()->json(['error' => 'Supplier code already exists for this business.'], 422);
            }
        }

        // Exclude 'balance' from updates to protect ledger integrity
        $supplier->update($request->only(['name', 'code', 'contact_person', 'phone', 'email', 'address']));

        return response()->json($supplier);
    }

    public function destroy(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('manage suppliers')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $supplier = Supplier::where('business_id', $businessId)->findOrFail($id);

        // Safety check: Cannot delete supplier with purchases, payments, or non-zero balance
        if ($supplier->purchases()->exists()) {
            return response()->json(['error' => 'Cannot delete supplier with existing purchase records.'], 422);
        }
        if ($supplier->payments()->exists()) {
            return response()->json(['error' => 'Cannot delete supplier with existing payment records.'], 422);
        }
        if ((float) $supplier->balance != 0.0) {
            return response()->json(['error' => 'Cannot delete supplier with non-zero outstanding balance.'], 422);
        }

        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted successfully.']);
    }
}
