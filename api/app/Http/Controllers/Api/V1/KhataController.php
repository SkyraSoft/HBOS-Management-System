<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\KhataTransaction;
use Illuminate\Http\Request;

class KhataController extends Controller
{
    protected function getBusinessId(Request $request): int
    {
        return ResolveActiveBusiness::requireActiveBusinessId();
    }

    /**
     * Read-only historical legacy Khata log listing.
     */
    public function index(Request $request)
    {
        $businessId = $this->getBusinessId($request);
        $transactions = KhataTransaction::with('customer')
            ->where('business_id', $businessId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($transactions);
    }

    /**
     * Disabled write endpoint.
     */
    public function store(Request $request)
    {
        return response()->json([
            'message' => 'Direct manual Khata balance mutation is deprecated and disabled in Phase 6. Please use the Customer Payment collection API (/customers/{id}/payments).'
        ], 410);
    }

    /**
     * Disabled write endpoint.
     */
    public function destroy(Request $request, string $id)
    {
        return response()->json([
            'message' => 'Legacy Khata transaction deletion is disabled in Phase 6. Please use the Customer Payment reversal API (/customer-payments/{id}/reverse).'
        ], 410);
    }
}
