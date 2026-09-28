<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasPermissionTo('view categories')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $categories = Category::with('subcategories')
            ->where('business_id', $businessId)
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasPermissionTo('manage categories')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $name = trim($request->name);
        $category = Category::firstOrCreate(
            [
                'business_id' => $businessId,
                'name' => $name
            ],
            [
                'description' => $request->description
            ]
        );

        return response()->json($category->load('subcategories'), 201);
    }

    public function show(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('view categories')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $category = Category::with('subcategories')
            ->where('business_id', $businessId)
            ->findOrFail($id);

        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('manage categories')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $category = Category::where('business_id', $businessId)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $category->update([
            'name' => trim($request->name),
            'description' => $request->description
        ]);

        return response()->json($category->load('subcategories'));
    }

    public function destroy(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('manage categories')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $category = Category::where('business_id', $businessId)->findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted']);
    }
}
