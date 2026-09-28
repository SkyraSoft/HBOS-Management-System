<?php

namespace App\Services;

use App\Models\User;
use App\Models\Branch;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserGovernanceService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * List all users belonging to the active business.
     */
    public function listUsers(int $businessId, array $filters = [])
    {
        setPermissionsTeamId($businessId);

        $query = User::whereHas('businesses', function ($q) use ($businessId) {
            $q->where('businesses.id', $businessId);
        })->with(['branch:id,name,code', 'roles']);

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $isActive = filter_var($filters['status'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        return $query->orderBy('name', 'asc')->get();
    }

    /**
     * Create a staff user under the active business.
     */
    public function createUser(int $businessId, array $data, User $actor): User
    {
        $business = Business::findOrFail($businessId);

        // Validate branch belongs to this business
        $branchId = $data['branch_id'] ?? null;
        if ($branchId) {
            $branchExists = Branch::where('business_id', $businessId)->where('id', $branchId)->exists();
            if (!$branchExists) {
                throw ValidationException::withMessages([
                    'branch_id' => ['The selected branch does not belong to this business.']
                ]);
            }
        }

        $roleName = $data['role'] ?? 'Salesperson';
        $allowedRoles = ['Business Owner', 'Branch Manager', 'Salesperson'];
        if (!in_array($roleName, $allowedRoles, true)) {
            throw ValidationException::withMessages([
                'role' => ["Invalid role specified. Allowed: " . implode(', ', $allowedRoles)]
            ]);
        }

        // Creating another Business Owner strictly requires the actor to be a Business Owner
        if ($roleName === 'Business Owner' && !$actor->hasRole('Business Owner')) {
            throw ValidationException::withMessages([
                'role' => ['Only an existing Business Owner can create another Business Owner account.']
            ]);
        }

        return DB::transaction(function () use ($business, $businessId, $data, $branchId, $roleName, $actor) {
            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'role' => $roleName,
                'is_active' => true,
            ]);
            $user->disableLogging();
            $user->save();

            // Attach membership pivots
            $user->businesses()->syncWithoutDetaching([$businessId]);
            if ($branchId) {
                $user->branches()->syncWithoutDetaching([
                    $branchId => ['business_id' => $businessId]
                ]);
            }

            // Assign Spatie canonical role with team context
            setPermissionsTeamId($businessId);
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web', 'business_id' => $businessId]);
            $user->assignRole($role);

            // Audit staff creation
            $this->auditService->log(
                logName: 'governance',
                event: 'created',
                description: "Staff account '{$user->name}' created with role '{$roleName}'",
                subject: $user,
                properties: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $roleName,
                    'branch_id' => $branchId,
                ],
                branchId: $branchId,
                businessId: $businessId,
                causer: $actor
            );

            return $user->load(['branch', 'roles']);
        });
    }

    /**
     * Update an existing staff user.
     */
    public function updateUser(int $businessId, int $userId, array $data, User $actor): User
    {
        $user = User::whereHas('businesses', function ($q) use ($businessId) {
            $q->where('businesses.id', $businessId);
        })->findOrFail($userId);

        setPermissionsTeamId($businessId);
        $currentRole = $user->roles()->where('roles.business_id', $businessId)->value('name') ?? $user->role;

        // 1. Self-Governance Invariant: Cannot change own role
        if (isset($data['role']) && $data['role'] !== $currentRole) {
            if ($actor->id === $user->id) {
                throw ValidationException::withMessages([
                    'role' => ['You cannot modify your own assigned role.']
                ]);
            }

            $allowedRoles = ['Business Owner', 'Branch Manager', 'Salesperson'];
            if (!in_array($data['role'], $allowedRoles, true)) {
                throw ValidationException::withMessages([
                    'role' => ["Invalid role specified. Allowed: " . implode(', ', $allowedRoles)]
                ]);
            }
        }

        // Validate branch
        if (isset($data['branch_id']) && $data['branch_id']) {
            $branchExists = Branch::where('business_id', $businessId)->where('id', $data['branch_id'])->exists();
            if (!$branchExists) {
                throw ValidationException::withMessages([
                    'branch_id' => ['The selected branch does not belong to this business.']
                ]);
            }
        }

        return DB::transaction(function () use ($businessId, $user, $data, $currentRole, $actor) {
            // 2. Last Owner Safety Invariant inside transaction: Cannot demote the last Business Owner
            if (isset($data['role']) && $data['role'] !== $currentRole) {
                if ($currentRole === 'Business Owner' && $data['role'] !== 'Business Owner') {
                    if ($this->countActiveOwners($businessId, true) <= 1) {
                        throw ValidationException::withMessages([
                            'role' => ['Cannot demote the last Business Owner of this business.']
                        ]);
                    }
                }
            }

            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'branch_id' => $user->branch_id,
                'role' => $currentRole,
            ];

            if (isset($data['name'])) {
                $user->name = $data['name'];
            }
            if (isset($data['email'])) {
                $user->email = $data['email'];
            }
            if (isset($data['branch_id'])) {
                $user->branch_id = $data['branch_id'];
                $user->branches()->syncWithoutDetaching([
                    $data['branch_id'] => ['business_id' => $businessId]
                ]);
            }

            if (isset($data['role']) && $data['role'] !== $currentRole) {
                $newRoleName = $data['role'];
                $user->role = $newRoleName;
                setPermissionsTeamId($businessId);
                $newRole = Role::firstOrCreate(['name' => $newRoleName, 'guard_name' => 'web', 'business_id' => $businessId]);
                $user->syncRoles([$newRole]);
            }

            $user->disableLogging();
            $user->save();

            $newValues = [
                'name' => $user->name,
                'email' => $user->email,
                'branch_id' => $user->branch_id,
                'role' => $user->role,
            ];

            $this->auditService->logMutation(
                category: 'governance',
                event: 'updated',
                description: "Staff account '{$user->name}' updated",
                subject: $user,
                oldValues: $oldValues,
                newValues: $newValues,
                branchId: $user->branch_id
            );

            return $user->load(['branch', 'roles']);
        });
    }

    /**
     * Toggle active/inactive status of a staff member.
     */
    public function toggleUserStatus(int $businessId, int $userId, bool $isActive, User $actor): User
    {
        $user = User::whereHas('businesses', function ($q) use ($businessId) {
            $q->where('businesses.id', $businessId);
        })->findOrFail($userId);

        // 1. Self-Governance Invariant: Cannot deactivate self
        if ($actor->id === $user->id && !$isActive) {
            throw ValidationException::withMessages([
                'is_active' => ['You cannot deactivate your own account.']
            ]);
        }

        return DB::transaction(function () use ($businessId, $user, $isActive, $actor) {
            // 2. Last Owner Safety Invariant inside transaction: Cannot deactivate the last Business Owner
            setPermissionsTeamId($businessId);
            $isOwner = $user->roles()->where('roles.business_id', $businessId)->where('name', 'Business Owner')->exists()
                || $user->role === 'Business Owner';

            if ($isOwner && !$isActive) {
                if ($this->countActiveOwners($businessId, true) <= 1) {
                    throw ValidationException::withMessages([
                        'is_active' => ['Cannot deactivate the last active Business Owner.']
                    ]);
                }
            }

            $oldStatus = $user->is_active;
            $user->is_active = $isActive;
            $user->disableLogging();
            $user->save();

            // If deactivated, immediately revoke all existing session tokens
            if (!$isActive) {
                $user->tokens()->delete();
            }

            $event = $isActive ? 'activated' : 'deactivated';
            $this->auditService->log(
                logName: 'governance',
                event: $event,
                description: "Staff account '{$user->name}' " . ($isActive ? 'activated' : 'deactivated'),
                subject: $user,
                properties: [
                    'old_status' => $oldStatus,
                    'new_status' => $isActive,
                ],
                branchId: $user->branch_id,
                businessId: $businessId,
                causer: $actor
            );

            return $user->load(['branch', 'roles']);
        });
    }

    /**
     * Count the number of active Business Owners for the business.
     */
    public function countActiveOwners(int $businessId, bool $lock = false): int
    {
        setPermissionsTeamId($businessId);

        $query = User::whereHas('businesses', function ($q) use ($businessId) {
            $q->where('businesses.id', $businessId);
        })
        ->where('is_active', true)
        ->where(function ($query) use ($businessId) {
            $query->whereHas('roles', function ($q) use ($businessId) {
                $q->where('name', 'Business Owner')
                  ->where('roles.business_id', $businessId);
            })->orWhere('role', 'Business Owner');
        });

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->count();
    }
}
