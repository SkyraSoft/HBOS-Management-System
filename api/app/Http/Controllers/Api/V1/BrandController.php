<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasPermissionTo('view brands')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $brands = Brand::with(['products.category'])
            ->where('business_id', $businessId)
            ->get();

        return response()->json($brands);
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasPermissionTo('manage brands')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable'
        ]);

        $imagePath = null;
        try {
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $path = $request->file('image')->store('brands', 'public');
                $imagePath = asset('storage/' . $path);
            } else if ($request->filled('image_url')) {
                $imagePath = $request->image_url;
            } else if ($request->filled('image') && is_string($request->image) && str_starts_with($request->image, 'data:image')) {
                $image_64 = $request->image;
                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
                $replace = substr($image_64, 0, strpos($image_64, ',')+1);
                $image = str_replace($replace, '', $image_64);
                $image = str_replace(' ', '+', $image);
                $imageName = 'brand_' . time() . '_' . uniqid() . '.' . $extension;
                \Illuminate\Support\Facades\Storage::disk('public')->put('brands/' . $imageName, base64_decode($image));
                $imagePath = asset('storage/brands/' . $imageName);
            } else if ($request->filled('image') && is_string($request->image)) {
                $imagePath = $request->image;
            }
        } catch (\Exception $e) {
            \Log::warning('Brand image upload warning: ' . $e->getMessage());
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $name = trim($request->name);
        $brand = Brand::firstOrCreate(
            [
                'business_id' => $businessId,
                'name' => $name
            ],
            [
                'image' => $imagePath
            ]
        );

        if ($imagePath && $brand->image !== $imagePath) {
            $brand->image = $imagePath;
            $brand->save();
        }

        return response()->json($brand->load('products.category'), 201);
    }

    public function show(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('view brands')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $brand = Brand::where('business_id', $businessId)->findOrFail($id);
        return response()->json($brand);
    }

    public function update(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('manage brands')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $brand = Brand::where('business_id', $businessId)->findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable'
        ]);

        $imagePath = $brand->image;
        try {
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $path = $request->file('image')->store('brands', 'public');
                $imagePath = asset('storage/' . $path);
            } else if ($request->filled('image_url')) {
                $imagePath = $request->image_url;
            } else if ($request->filled('image') && is_string($request->image) && str_starts_with($request->image, 'data:image')) {
                $image_64 = $request->image;
                $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];
                $replace = substr($image_64, 0, strpos($image_64, ',')+1);
                $image = str_replace($replace, '', $image_64);
                $image = str_replace(' ', '+', $image);
                $imageName = 'brand_' . time() . '_' . uniqid() . '.' . $extension;
                \Illuminate\Support\Facades\Storage::disk('public')->put('brands/' . $imageName, base64_decode($image));
                $imagePath = asset('storage/brands/' . $imageName);
            } else if ($request->has('image') && is_string($request->image)) {
                $imagePath = $request->image;
            }
        } catch (\Exception $e) {
            \Log::warning('Brand image update warning: ' . $e->getMessage());
        }

        $brand->update([
            'name' => trim($request->name),
            'image' => $imagePath
        ]);

        return response()->json($brand->load('products.category'));
    }

    public function destroy(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('manage brands')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $brand = Brand::where('business_id', $businessId)->findOrFail($id);
        $brand->delete();

        return response()->json(['message' => 'Brand deleted']);
    }
}
