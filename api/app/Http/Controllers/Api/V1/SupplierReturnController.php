<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\SupplierReturn;
use App\Services\SupplierReturnService;
use Illuminate\Http\Request;
use Exception;

class SupplierReturnController extends Controller
{
    protected $supplierReturnService;

    public function __construct(SupplierReturnService $supplierReturnService)
    {
        $this->supplierReturnService = $supplierReturnService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('view purchases')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $query = SupplierReturn::with(['items.product', 'supplier', 'branch', 'creator'])
            ->where('business_id', $businessId);

        if ($user->hasRole('Branch Manager') && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->has('supplier_id') && $request->supplier_id) {
            $query->where('supplier_id', intval($request->supplier_id));
        }

        $returns = $query->orderBy('created_at', 'desc')->get();
        return response()->json($returns);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('return purchases')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'supplier_id' => 'required|integer',
            'branch_id' => 'nullable|integer',
            'purchase_id' => 'nullable|integer',
            'return_number' => 'nullable|string|max:100',
            'reason' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        try {
            $supplierReturn = $this->supplierReturnService->createSupplierReturn($request->all(), $user);
            return response()->json($supplierReturn, 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('view purchases')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $query = SupplierReturn::with(['items.product', 'supplier', 'branch', 'creator'])
            ->where('business_id', $businessId);

        if ($user->hasRole('Branch Manager') && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $supplierReturn = $query->findOrFail($id);
        return response()->json($supplierReturn);
    }
}
