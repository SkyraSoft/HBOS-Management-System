<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Branch;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ViewAsController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Enter View-As read-only preview mode.
     * REQ-0006 / SRT-GAP-020: Owner only.
     */
    public function enter(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Unauthorized: Only Business Owners can access View-As preview mode.'], 403);
        }

        $activeBusinessId = ResolveActiveBusiness::requireActiveBusinessId();

        $validated = $request->validate([
            'branch_id' => 'required|integer',
            'role' => 'required|string|in:Branch Manager,Cashier',
        ]);

        $branch = Branch::where('business_id', $activeBusinessId)->findOrFail($validated['branch_id']);

        $this->auditService->log(
            logName: 'governance',
            event: 'view_as_entered',
            description: "Owner entered View-As preview mode for Branch '{$branch->name}' as '{$validated['role']}'",
            subject: $branch,
            properties: [
                'branch_id' => $branch->id,
                'role' => $validated['role'],
                'owner_id' => $user->id,
            ],
            branchId: $branch->id
        );

        return response()->json([
            'message' => "Entered View-As preview mode as {$validated['role']} for {$branch->name}.",
            'data' => [
                'active' => true,
                'role' => $validated['role'],
                'branch_id' => (int) $branch->id,
                'branch_name' => $branch->name,
                'is_read_only' => true,
            ]
        ]);
    }

    /**
     * Exit View-As preview mode.
     */
    public function exit(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Unauthorized: Only Business Owners can access View-As mode.'], 403);
        }

        $this->auditService->log(
            logName: 'governance',
            event: 'view_as_exited',
            description: "Owner exited View-As preview mode",
            subject: null,
            properties: [
                'owner_id' => $user->id,
            ]
        );

        return response()->json([
            'message' => 'Exited View-As preview mode. Standard Owner authority restored.',
            'data' => [
                'active' => false,
                'is_read_only' => false,
            ]
        ]);
    }
}
