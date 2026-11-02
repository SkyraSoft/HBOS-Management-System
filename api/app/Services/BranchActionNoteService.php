<?php

namespace App\Services;

use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Branch;
use App\Models\BranchActionNote;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class BranchActionNoteService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Verify whether a user is authorized for a specific branch.
     */
    public function isUserAuthorizedForBranch(User $user, int $branchId): bool
    {
        if ($user->hasRole('Business Owner')) {
            return true;
        }

        if ((int) $user->branch_id === $branchId) {
            return true;
        }

        if (method_exists($user, 'branches')) {
            $assigned = $user->branches()->pluck('branches.id')->map(fn($id) => (int) $id)->toArray();
            return in_array($branchId, $assigned, true);
        }

        return false;
    }

    /**
     * List action notes filtered by authorized branch scope.
     */
    public function listNotes(array $filters, User $actor)
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();

        $query = BranchActionNote::where('business_id', $businessId)
            ->with(['branch', 'createdBy', 'assignedTo', 'resolvedBy'])
            ->latest();

        if ($actor->hasRole('Business Owner')) {
            if (!empty($filters['branch_id'])) {
                $query->where('branch_id', (int) $filters['branch_id']);
            }
        } elseif ($actor->hasRole('Branch Manager')) {
            $assignedBranchIds = [];
            if ($actor->branch_id) {
                $assignedBranchIds[] = (int) $actor->branch_id;
            }
            if (method_exists($actor, 'branches')) {
                $fromPivot = $actor->branches()->pluck('branches.id')->map(fn($id) => (int) $id)->toArray();
                $assignedBranchIds = array_unique(array_merge($assignedBranchIds, $fromPivot));
            }

            if (!empty($filters['branch_id'])) {
                $reqBranch = (int) $filters['branch_id'];
                if (!in_array($reqBranch, $assignedBranchIds, true)) {
                    throw new Exception("Unauthorized: You do not have access to notes for this branch.");
                }
                $query->where('branch_id', $reqBranch);
            } else {
                $query->whereIn('branch_id', $assignedBranchIds);
            }
        } else {
            throw new Exception("Unauthorized: Cashiers and unauthorized roles cannot access branch action notes.");
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->get();
    }

    /**
     * Create a new action note (Owner only).
     */
    public function createNote(array $data, User $actor): BranchActionNote
    {
        if (!$actor->hasRole('Business Owner')) {
            throw new Exception("Unauthorized: Only Business Owners can delegate branch action notes.");
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $branchId = (int) ($data['branch_id'] ?? 0);

        if ($branchId <= 0) {
            throw new Exception("Target branch is required.");
        }

        $branch = Branch::where('business_id', $businessId)->findOrFail($branchId);

        $assignedTo = !empty($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        if ($assignedTo) {
            $assignee = User::where('business_id', $businessId)->findOrFail($assignedTo);
            if ($assignee->is_active !== null && !$assignee->is_active) {
                throw new Exception("Assignee account is deactivated.");
            }
            if (!$this->isUserAuthorizedForBranch($assignee, $branchId)) {
                throw new Exception("Assignee is not an authorized member of the target branch.");
            }
            if (!$assignee->hasRole(['Branch Manager', 'Inventory Manager', 'Manager'])) {
                throw new Exception("Assignee must have a Branch Manager role.");
            }
        }

        $note = BranchActionNote::create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'created_by' => $actor->id,
            'assigned_to' => $assignedTo,
            'title' => trim($data['title']),
            'description' => $data['description'] ?? null,
            'priority' => in_array($data['priority'] ?? '', ['low', 'medium', 'high', 'urgent']) ? $data['priority'] : 'medium',
            'status' => 'open',
        ]);

        $this->auditService->log(
            logName: 'governance',
            event: 'created',
            description: "Owner delegated action note '{$note->title}' to branch '{$branch->name}'",
            subject: $note,
            properties: [
                'note_id' => $note->id,
                'branch_id' => $branchId,
                'assigned_to' => $assignedTo,
                'priority' => $note->priority,
            ],
            branchId: $branchId
        );

        return $note->load(['branch', 'createdBy', 'assignedTo']);
    }

    /**
     * Update the status of an action note enforcing the state machine.
     */
    public function updateStatus(int $noteId, string $targetStatus, array $payload, User $actor): BranchActionNote
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();

        $note = BranchActionNote::where('business_id', $businessId)->findOrFail($noteId);
        $currentStatus = $note->status;

        // Verify authorization
        $isOwner = $actor->hasRole('Business Owner');
        $isManager = $actor->hasRole('Branch Manager');

        if (!$isOwner && !$isManager) {
            throw new Exception("Unauthorized: Role cannot update action notes.");
        }

        if ($isManager && !$this->isUserAuthorizedForBranch($actor, (int) $note->branch_id)) {
            throw new Exception("Unauthorized: You cannot alter action notes for an unassigned branch.");
        }

        // Validate state transitions
        if ($currentStatus === 'open' && $targetStatus === 'in_progress') {
            $note->status = 'in_progress';
        } elseif ($currentStatus === 'in_progress' && $targetStatus === 'resolved') {
            $resNotes = trim($payload['resolution_notes'] ?? '');
            if (empty($resNotes)) {
                throw new Exception("Mandatory resolution notes are required to resolve an action note.");
            }
            $note->status = 'resolved';
            $note->resolution_notes = $resNotes;
            $note->resolved_at = now();
            $note->resolved_by = $actor->id;
        } elseif ($currentStatus === 'open' && $targetStatus === 'resolved') {
            if (!$isOwner) {
                throw new Exception("Only the Business Owner can immediately resolve an open action note.");
            }
            $resNotes = trim($payload['resolution_notes'] ?? '');
            if (empty($resNotes)) {
                throw new Exception("Mandatory resolution notes are required to resolve an action note.");
            }
            $note->status = 'resolved';
            $note->resolution_notes = $resNotes;
            $note->resolved_at = now();
            $note->resolved_by = $actor->id;
        } else {
            throw new Exception("Invalid status transition from '{$currentStatus}' to '{$targetStatus}'.");
        }

        $note->save();

        $this->auditService->log(
            logName: 'governance',
            event: 'status_updated',
            description: "Action note #{$note->id} transitioned to '{$targetStatus}'",
            subject: $note,
            properties: [
                'note_id' => $note->id,
                'from_status' => $currentStatus,
                'to_status' => $targetStatus,
                'resolved_by' => $note->resolved_by,
            ],
            branchId: $note->branch_id
        );

        return $note->fresh(['branch', 'createdBy', 'assignedTo', 'resolvedBy']);
    }

    /**
     * Get pending action notes count for Owner command / badges.
     */
    public function getPendingCount(User $actor): int
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();

        $query = BranchActionNote::where('business_id', $businessId)
            ->whereIn('status', ['open', 'in_progress']);

        if (!$actor->hasRole('Business Owner')) {
            $assignedBranchIds = [];
            if ($actor->branch_id) {
                $assignedBranchIds[] = (int) $actor->branch_id;
            }
            if (method_exists($actor, 'branches')) {
                $fromPivot = $actor->branches()->pluck('branches.id')->map(fn($id) => (int) $id)->toArray();
                $assignedBranchIds = array_unique(array_merge($assignedBranchIds, $fromPivot));
            }
            $query->whereIn('branch_id', $assignedBranchIds);
        }

        return $query->count();
    }
}
