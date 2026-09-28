<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasPermissionTo('view categories')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $subcategories = Subcategory::with('category')
            ->where('business_id', $businessId)
            ->get();

        return response()->json($subcategories);
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasPermissionTo('manage categories')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'category_id' => 'nullable',
            'name' => 'required|string|max:255'
        ]);

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $name = trim($request->name);
        $categoryId = $request->filled('category_id') ? $request->category_id : null;

        if (!empty($categoryId)) {
            $catExists = Category::where('business_id', $businessId)->where('id', $categoryId)->exists();
            if (!$catExists) {
                return response()->json(['message' => 'Invalid category specified.'], 422);
            }
        }
        
        // If parent category name is provided instead of ID
        if (empty($categoryId) && $request->filled('parent_category')) {
            $parentCat = Category::firstOrCreate([
                'business_id' => $businessId,
                'name' => trim($request->parent_category)
            ]);
            $categoryId = $parentCat->id;
        }

        $subcategory = Subcategory::firstOrCreate(
            [
                'business_id' => $businessId,
                'name' => $name,
                'category_id' => $categoryId
            ]
        );

        return response()->json($subcategory->load('category'), 201);
    }

    public function show(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('view categories')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $subcategory = Subcategory::with('category')
            ->where('business_id', $businessId)
            ->findOrFail($id);

        return response()->json($subcategory);
    }

    public function update(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('manage categories')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $subcategory = Subcategory::where('business_id', $businessId)->findOrFail($id);
        
        $request->validate([
            'category_id' => 'nullable',
            'name' => 'required|string|max:255'
        ]);

        $data = ['name' => trim($request->name)];
        if ($request->has('category_id')) {
            $catId = $request->category_id ?: null;
            if (!empty($catId)) {
                $catExists = Category::where('business_id', $businessId)->where('id', $catId)->exists();
                if (!$catExists) {
                    return response()->json(['message' => 'Invalid category specified.'], 422);
                }
            }
            $data['category_id'] = $catId;
        }

        $subcategory->update($data);

        return response()->json($subcategory->load('category'));
    }

    public function destroy(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('manage categories')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $subcategory = Subcategory::where('business_id', $businessId)->findOrFail($id);
        $subcategory->delete();

        return response()->json(['message' => 'Subcategory deleted']);
    }
}
