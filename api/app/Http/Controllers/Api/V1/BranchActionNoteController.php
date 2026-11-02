<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BranchActionNoteService;
use Exception;
use Illuminate\Http\Request;

class BranchActionNoteController extends Controller
{
    protected BranchActionNoteService $actionNoteService;

    public function __construct(BranchActionNoteService $actionNoteService)
    {
        $this->actionNoteService = $actionNoteService;
    }

    /**
     * Display a listing of action notes.
     */
    public function index(Request $request)
    {
        try {
            $notes = $this->actionNoteService->listNotes($request->all(), $request->user());
            return response()->json(['data' => $notes]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    /**
     * Store a newly created action note (Owner only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|integer',
        ]);

        try {
            $note = $this->actionNoteService->createNote($validated, $request->user());
            return response()->json([
                'message' => 'Action note created and delegated successfully.',
                'data' => $note
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Update the status of an action note.
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:open,in_progress,resolved',
            'resolution_notes' => 'nullable|string',
        ]);

        try {
            $note = $this->actionNoteService->updateStatus((int) $id, $validated['status'], $validated, $request->user());
            return response()->json([
                'message' => "Action note status updated to '{$validated['status']}'.",
                'data' => $note
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get count of pending action notes.
     */
    public function pendingCount(Request $request)
    {
        try {
            $count = $this->actionNoteService->getPendingCount($request->user());
            return response()->json([
                'count' => $count,
                'data' => ['pending_count' => $count]
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }
}
