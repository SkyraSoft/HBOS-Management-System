<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Http\Middleware\ResolveActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExpenseCategoryController extends Controller
{
    protected function getActiveBusinessId(Request $request): int
    {
        return ResolveActiveBusiness::requireActiveBusinessId();
    }

    public function index(Request $request)
    {
        $businessId = $this->getActiveBusinessId($request);
        $categories = ExpenseCategory::where('business_id', $businessId)->orderBy('name', 'asc')->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $businessId = $this->getActiveBusinessId($request);
        $cleanName = trim($request->name);
        $normalizedName = mb_strtolower($cleanName);

        // Normalized duplicate check within same business
        $existing = ExpenseCategory::where('business_id', $businessId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
            ->first();

        if ($existing) {
            return response()->json($existing, 200); // Idempotent return if already exists
        }

        $category = ExpenseCategory::create([
            'business_id' => $businessId,
            'name' => $cleanName, // Preserve clean display casing
        ]);

        return response()->json($category, 201);
    }

    public function show(Request $request, string $id)
    {
        $businessId = $this->getActiveBusinessId($request);
        $category = ExpenseCategory::where('business_id', $businessId)->findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        $businessId = $this->getActiveBusinessId($request);
        $category = ExpenseCategory::where('business_id', $businessId)->findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $cleanName = trim($request->name);
        $normalizedName = mb_strtolower($cleanName);

        $existing = ExpenseCategory::where('business_id', $businessId)
            ->where('id', '!=', $category->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'name' => ['Expense category name already exists for this business.']
            ]);
        }

        $category->update([
            'name' => $cleanName
        ]);

        return response()->json($category);
    }

    public function destroy(Request $request, string $id)
    {
        $businessId = $this->getActiveBusinessId($request);
        $category = ExpenseCategory::where('business_id', $businessId)->findOrFail($id);
        $category->delete();

        return response()->json(null, 204);
    }
}
