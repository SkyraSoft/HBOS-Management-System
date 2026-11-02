<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserGovernanceService;
use Illuminate\Http\Request;

class UserGovernanceController extends Controller
{
    protected UserGovernanceService $userGovernanceService;

    public function __construct(UserGovernanceService $userGovernanceService)
    {
        $this->userGovernanceService = $userGovernanceService;
    }

    /**
     * Display a listing of staff users for the active business.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->hasPermissionTo('manage users')) {
            return response()->json(['message' => 'Insufficient permission to manage staff users.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $filters = $request->only(['search', 'branch_id', 'status']);
        $users = $this->userGovernanceService->listUsers($activeBusinessId, $filters);

        return response()->json(['data' => $users]);
    }

    /**
     * Create a staff user under the active business.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->hasPermissionTo('manage users')) {
            return response()->json(['message' => 'Insufficient permission to manage staff users.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'employee_code' => 'nullable|string|max:50',
            'job_title' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|in:Business Owner,Branch Manager,Salesperson,Accountant,Inventory Manager',
            'branch_id' => 'nullable|integer',
            'salary_amount' => 'nullable|numeric|min:0',
            'payment_cycle' => 'nullable|string|in:monthly,weekly,biweekly,daily',
            'next_payment_date' => 'nullable|date',
        ]);

        $createdUser = $this->userGovernanceService->createUser($activeBusinessId, $validated, $user);

        $responseData = [
            'message' => 'Staff user created successfully.',
            'data' => $createdUser,
        ];

        if (isset($createdUser->plain_invitation_token)) {
            $responseData['invitation_token'] = $createdUser->plain_invitation_token;
        }

        return response()->json($responseData, 201);
    }

    /**
     * Display details for a specific staff user.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->hasPermissionTo('manage users')) {
            return response()->json(['message' => 'Insufficient permission to manage staff users.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $targetUser = User::whereHas('businesses', function ($q) use ($activeBusinessId) {
            $q->where('businesses.id', $activeBusinessId);
        })->with(['branch:id,name,code', 'roles'])->findOrFail($id);

        return response()->json(['data' => $targetUser]);
    }

    /**
     * Update an existing staff user.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->hasPermissionTo('manage users')) {
            return response()->json(['message' => 'Insufficient permission to manage staff users.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:50',
            'employee_code' => 'nullable|string|max:50',
            'job_title' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'role' => 'sometimes|required|string|in:Business Owner,Branch Manager,Salesperson,Accountant,Inventory Manager',
            'branch_id' => 'nullable|integer',
            'salary_amount' => 'nullable|numeric|min:0',
            'payment_cycle' => 'nullable|string|in:monthly,weekly,biweekly,daily',
            'next_payment_date' => 'nullable|date',
        ]);

        $updatedUser = $this->userGovernanceService->updateUser($activeBusinessId, (int) $id, $validated, $user);

        return response()->json([
            'message' => 'Staff user updated successfully.',
            'data' => $updatedUser,
        ]);
    }

    /**
     * Toggle active/inactive status of a staff user.
     */
    public function toggleStatus(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasRole('Business Owner') && !$user->hasPermissionTo('manage users')) {
            return response()->json(['message' => 'Insufficient permission to manage staff users.'], 403);
        }

        $activeBusinessId = app()->has('active_business_id') ? (int) app('active_business_id') : 0;
        if ($activeBusinessId <= 0) {
            return response()->json(['message' => 'Active business context is required.'], 400);
        }

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $updatedUser = $this->userGovernanceService->toggleUserStatus($activeBusinessId, (int) $id, (bool) $validated['is_active'], $user);

        return response()->json([
            'message' => 'User status updated successfully.',
            'data' => $updatedUser,
        ]);
    }
}
