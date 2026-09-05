<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;
        $subcategories = Subcategory::with('category')
            ->where('business_id', $businessId)
            ->get();
            
        if ($subcategories->isEmpty()) {
            $subcategories = Subcategory::with('category')->get();
        }
        return response()->json($subcategories);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable',
            'name' => 'required|string|max:255'
        ]);

        $name = trim($request->name);
        $categoryId = $request->filled('category_id') ? $request->category_id : null;
        
        // If parent category name is provided instead of ID
        if (empty($categoryId) && $request->filled('parent_category')) {
            $parentCat = Category::firstOrCreate([
                'business_id' => $request->user()->business_id,
                'name' => trim($request->parent_category)
            ]);
            $categoryId = $parentCat->id;
        }

        $subcategory = Subcategory::firstOrCreate(
            [
                'business_id' => $request->user()->business_id,
                'name' => $name,
                'category_id' => $categoryId
            ]
        );

        return response()->json($subcategory->load('category'), 201);
    }

    public function show(Request $request, string $id)
    {
        $subcategory = Subcategory::with('category')
            ->where('business_id', $request->user()->business_id)
            ->findOrFail($id);
        return response()->json($subcategory);
    }

    public function update(Request $request, string $id)
    {
        $subcategory = Subcategory::where('business_id', $request->user()->business_id)->findOrFail($id);
        
        $request->validate([
            'category_id' => 'nullable',
            'name' => 'required|string|max:255'
        ]);

        $data = ['name' => trim($request->name)];
        if ($request->has('category_id')) {
            $data['category_id'] = $request->category_id ?: null;
        }

        $subcategory->update($data);

        return response()->json($subcategory->load('category'));
    }

    public function destroy(Request $request, string $id)
    {
        $subcategory = Subcategory::where('business_id', $request->user()->business_id)->findOrFail($id);
        $subcategory->delete();

        return response()->json(['message' => 'Subcategory deleted']);
    }
}
