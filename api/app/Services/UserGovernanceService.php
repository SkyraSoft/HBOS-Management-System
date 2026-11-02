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

    public static array $canonicalRolePermissions = [
        'Business Owner' => null, // all permissions
        'Branch Manager' => [
            'manage branch settings',
            'manage local inventory',
            'view branch reports',
            'create sales',
            'view own sales',
            'create customers',
            'view products',
            'manage products',
            'view categories',
            'manage categories',
            'view brands',
            'manage brands',
            'view inventory',
            'adjust inventory',
            'transfer inventory',
            'view stock movements',
            'view suppliers',
            'view purchases',
            'manage purchases',
            'return purchases',
            'view sales',
            'manage sales',
            'return sales',
        ],
        'Salesperson' => [
            'view sales',
            'create sales',
            'view own sales',
            'create customers',
            'view products',
            'view categories',
            'view brands',
            'view inventory',
        ],
        'Accountant' => [
            'view full reports',
            'view branch reports',
            'view sales',
            'view own sales',
            'create customers',
            'view suppliers',
            'manage suppliers',
            'record supplier payments',
            'view purchases',
            'view products',
            'view categories',
            'view brands',
            'view inventory',
        ],
        'Inventory Manager' => [
            'view products',
            'manage products',
            'view categories',
            'manage categories',
            'view brands',
            'manage brands',
            'view inventory',
            'manage local inventory',
            'adjust inventory',
            'transfer inventory',
            'view stock movements',
            'view suppliers',
            'view purchases',
            'manage purchases',
            'return purchases',
        ],
    ];

    public static function syncCanonicalPermissionsToRole(Role $role): void
    {
        $roleName = $role->name;
        if (!array_key_exists($roleName, self::$canonicalRolePermissions)) {
            return;
        }

        $allPermissionNames = [
            'manage business settings',
            'manage branches',
            'manage users',
            'view full reports',
            'manage branch settings',
            'manage local inventory',
            'view branch reports',
            'create sales',
            'view own sales',
            'create customers',
            'view products',
            'manage products',
            'view categories',
            'manage categories',
            'view brands',
            'manage brands',
            'view inventory',
            'adjust inventory',
            'transfer inventory',
            'view stock movements',
            'view suppliers',
            'manage suppliers',
            'view purchases',
            'manage purchases',
            'return purchases',
            'record supplier payments',
            'view sales',
            'manage sales',
            'return sales',
            'override sale price'
        ];

        // Ensure permissions exist in database
        foreach ($allPermissionNames as $permName) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        if (self::$canonicalRolePermissions[$roleName] === null) {
            // Business Owner gets all permissions
            $allPerms = \Spatie\Permission\Models\Permission::where('guard_name', 'web')->get();
            $role->syncPermissions($allPerms);
        } else {
            $role->syncPermissions(self::$canonicalRolePermissions[$roleName]);
        }
    }

    public static function getOrCreateTenantRole(string $roleName, int $businessId): Role
    {
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($businessId);
        }
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
            'business_id' => $businessId,
        ]);
        if ($role->permissions()->count() === 0) {
            self::syncCanonicalPermissionsToRole($role);
        }
        return $role;
    }

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

        $roleName = $data['role'] ?? 'Salesperson';
        $allowedRoles = ['Business Owner', 'Branch Manager', 'Salesperson', 'Accountant', 'Inventory Manager'];
        if (!in_array($roleName, $allowedRoles, true)) {
            throw ValidationException::withMessages([
                'role' => ["Invalid role specified. Allowed: " . implode(', ', $allowedRoles)]
            ]);
        }

        // Validate branch belongs to this business and is required for branch-scoped roles
        $branchId = $data['branch_id'] ?? null;
        if (in_array($roleName, ['Branch Manager', 'Salesperson'], true) && empty($branchId)) {
            throw ValidationException::withMessages([
                'branch_id' => ["The '{$roleName}' role requires an assigned branch."]
            ]);
        }
        if ($branchId) {
            $branchExists = Branch::where('business_id', $businessId)->where('id', $branchId)->exists();
            if (!$branchExists) {
                throw ValidationException::withMessages([
                    'branch_id' => ['The selected branch does not belong to this business.']
                ]);
            }
        }

        // Privilege Escalation Prevention: Creating Business Owner, Branch Manager, or Accountant strictly requires actor to be Business Owner
        $isActorOwner = $actor->hasRole('Business Owner');
        if (in_array($roleName, ['Business Owner', 'Branch Manager', 'Accountant'], true) && !$isActorOwner) {
            throw ValidationException::withMessages([
                'role' => ["Only a Business Owner can assign the '{$roleName}' role."]
            ]);
        }

        return DB::transaction(function () use ($business, $businessId, $data, $branchId, $roleName, $actor) {
            $plainToken = null;
            $invToken = null;
            $invExpires = null;
            $isActive = true;

            if (!empty($data['password'])) {
                $hashedPassword = Hash::make($data['password']);
            } else {
                // Secure Invitation Mode: generate cryptographically random single-use token
                $plainToken = \Illuminate\Support\Str::random(40);
                $invToken = Hash::make($plainToken);
                $invExpires = now()->addDays(3);
                $hashedPassword = Hash::make(\Illuminate\Support\Str::random(32));
                $isActive = false; // Account requires invitation acceptance
            }

            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'employee_code' => $data['employee_code'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'hire_date' => $data['hire_date'] ?? null,
                'password' => $hashedPassword,
                'invitation_token' => $invToken,
                'invitation_expires_at' => $invExpires,
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'role' => $roleName,
                'salary_amount' => $data['salary_amount'] ?? null,
                'payment_cycle' => $data['payment_cycle'] ?? null,
                'next_payment_date' => $data['next_payment_date'] ?? null,
                'is_active' => $isActive,
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

            // Assign Spatie canonical role with team context and canonical permissions
            $role = self::getOrCreateTenantRole($roleName, $businessId);
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
                    'phone' => $user->phone,
                    'role' => $roleName,
                    'branch_id' => $branchId,
                    'invitation_required' => !empty($plainToken),
                    'salary_amount' => $user->salary_amount,
                    'payment_cycle' => $user->payment_cycle,
                ],
                branchId: $branchId,
                businessId: $businessId,
                causer: $actor
            );

            if ($plainToken) {
                $user->plain_invitation_token = $plainToken;
            }

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

            $allowedRoles = ['Business Owner', 'Branch Manager', 'Salesperson', 'Accountant', 'Inventory Manager'];
            if (!in_array($data['role'], $allowedRoles, true)) {
                throw ValidationException::withMessages([
                    'role' => ["Invalid role specified. Allowed: " . implode(', ', $allowedRoles)]
                ]);
            }

            // Privilege Escalation Prevention: Assigning Business Owner, Branch Manager, or Accountant requires actor to be Business Owner
            if (in_array($data['role'], ['Business Owner', 'Branch Manager', 'Accountant'], true) && !$actor->hasRole('Business Owner')) {
                throw ValidationException::withMessages([
                    'role' => ["Only a Business Owner can assign the '{$data['role']}' role."]
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
                'phone' => $user->phone,
                'employee_code' => $user->employee_code,
                'job_title' => $user->job_title,
                'hire_date' => $user->hire_date,
                'branch_id' => $user->branch_id,
                'role' => $currentRole,
                'salary_amount' => $user->salary_amount,
                'payment_cycle' => $user->payment_cycle,
                'next_payment_date' => $user->next_payment_date,
            ];

            if (isset($data['name'])) {
                $user->name = $data['name'];
            }
            if (isset($data['email'])) {
                $user->email = $data['email'];
            }
            if (array_key_exists('phone', $data)) {
                $user->phone = $data['phone'];
            }
            if (array_key_exists('employee_code', $data)) {
                $user->employee_code = $data['employee_code'];
            }
            if (array_key_exists('job_title', $data)) {
                $user->job_title = $data['job_title'];
            }
            if (array_key_exists('hire_date', $data)) {
                $user->hire_date = $data['hire_date'];
            }
            if (isset($data['branch_id'])) {
                $user->branch_id = $data['branch_id'];
                $user->branches()->syncWithoutDetaching([
                    $data['branch_id'] => ['business_id' => $businessId]
                ]);
            }
            if (array_key_exists('salary_amount', $data)) {
                $user->salary_amount = $data['salary_amount'];
            }
            if (array_key_exists('payment_cycle', $data)) {
                $user->payment_cycle = $data['payment_cycle'];
            }
            if (array_key_exists('next_payment_date', $data)) {
                $user->next_payment_date = $data['next_payment_date'];
            }

            if (isset($data['role']) && $data['role'] !== $currentRole) {
                $newRoleName = $data['role'];
                $user->role = $newRoleName;
                $newRole = self::getOrCreateTenantRole($newRoleName, $businessId);
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
