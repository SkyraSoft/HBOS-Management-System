<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;
        $categories = Category::with('subcategories')
            ->where('business_id', $businessId)
            ->get();
            
        if ($categories->isEmpty()) {
            $categories = Category::with('subcategories')->get();
        }
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $name = trim($request->name);
        $category = Category::firstOrCreate(
            [
                'business_id' => $request->user()->business_id,
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
        $category = Category::with('subcategories')
            ->where('business_id', $request->user()->business_id)
            ->findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        $category = Category::where('business_id', $request->user()->business_id)->findOrFail($id);

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
        $category = Category::where('business_id', $request->user()->business_id)->findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted']);
    }
}
