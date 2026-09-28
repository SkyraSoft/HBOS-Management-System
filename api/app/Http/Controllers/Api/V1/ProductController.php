<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Subcategory;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasPermissionTo('view products')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $products = Product::with(['category', 'brand', 'subcategory'])->get();
        return response()->json($products);
    }

    private function getUnsplashFallback($name) {
        $n = strtolower($name);
        if (strpos($n, 'apple') !== false || strpos($n, 'fruit') !== false || strpos($n, 'banana') !== false || strpos($n, 'mango') !== false) {
            return 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'bread') !== false || strpos($n, 'wheat') !== false || strpos($n, 'bakery') !== false || strpos($n, 'flour') !== false || strpos($n, 'atta') !== false) {
            return 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'rice') !== false || strpos($n, 'grain') !== false || strpos($n, 'pulse') !== false || strpos($n, 'daal') !== false || strpos($n, 'dal') !== false) {
            return 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'milk') !== false || strpos($n, 'dairy') !== false || strpos($n, 'cheese') !== false || strpos($n, 'butter') !== false || strpos($n, 'yogurt') !== false) {
            return 'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'oil') !== false || strpos($n, 'ghee') !== false || strpos($n, 'cooking') !== false) {
            return 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'pepsi') !== false || strpos($n, 'coke') !== false || strpos($n, 'cola') !== false || strpos($n, 'drink') !== false || strpos($n, 'beverage') !== false || strpos($n, 'juice') !== false || strpos($n, 'soda') !== false) {
            return 'https://images.unsplash.com/photo-1629203851122-3726ecdf080e?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'egg') !== false) {
            return 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'snack') !== false || strpos($n, 'chips') !== false || strpos($n, 'biscuit') !== false || strpos($n, 'cookie') !== false) {
            return 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'tea') !== false || strpos($n, 'coffee') !== false) {
            return 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'sugar') !== false || strpos($n, 'salt') !== false || strpos($n, 'spice') !== false || strpos($n, 'masala') !== false) {
            return 'https://images.unsplash.com/photo-1588698144670-f80e927c9a96?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'vegetable') !== false || strpos($n, 'tomato') !== false || strpos($n, 'potato') !== false || strpos($n, 'onion') !== false) {
            return 'https://images.unsplash.com/photo-1597362925123-77861d3fbac7?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'iphone') !== false || strpos($n, 'mobile') !== false || strpos($n, 'phone') !== false || strpos($n, 'samsung') !== false || strpos($n, 'gelaxy') !== false) {
            return 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'watch') !== false || strpos($n, 'smartwatch') !== false) {
            return 'https://images.unsplash.com/photo-1542496658-e33a6d0d50f6?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'laptop') !== false || strpos($n, 'macbook') !== false || strpos($n, 'computer') !== false) {
            return 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'headphone') !== false || strpos($n, 'audio') !== false || strpos($n, 'earbud') !== false) {
            return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'mouse') !== false || strpos($n, 'keyboard') !== false) {
            return 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'tee') !== false || strpos($n, 'shirt') !== false || strpos($n, 'clothing') !== false) {
            return 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80';
        }
        if (strpos($n, 'jean') !== false || strpos($n, 'denim') !== false || strpos($n, 'pant') !== false) {
            return 'https://images.unsplash.com/photo-1542272604-780c96856592?auto=format&fit=crop&w=800&q=80';
        }
        return 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=800&q=80';
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasPermissionTo('manage products')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $activeBusinessId = ResolveActiveBusiness::requireActiveBusinessId();

        $request->validate([
            'category_id' => 'nullable',
            'brand_id' => 'nullable',
            'subcategory_id' => 'nullable',
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->where(function ($query) use ($activeBusinessId) {
                    return $query->where('business_id', $activeBusinessId);
                })
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->where(function ($query) use ($activeBusinessId) {
                    return $query->where('business_id', $activeBusinessId);
                })
            ],
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
            'image' => 'nullable',
            'has_variations' => 'nullable|boolean',
            'attributes' => 'nullable|array',
            'variations' => 'nullable|array'
        ]);

        // Cross-tenant relationship validation
        if ($request->filled('category_id') && is_numeric($request->category_id)) {
            $catExists = Category::where('business_id', $activeBusinessId)->where('id', $request->category_id)->exists();
            if (!$catExists) {
                return response()->json(['message' => 'Invalid category specified for active business.'], 422);
            }
        }
        if ($request->filled('brand_id') && is_numeric($request->brand_id)) {
            $brandExists = Brand::where('business_id', $activeBusinessId)->where('id', $request->brand_id)->exists();
            if (!$brandExists) {
                return response()->json(['message' => 'Invalid brand specified for active business.'], 422);
            }
        }
        if ($request->filled('subcategory_id') && is_numeric($request->subcategory_id)) {
            $subExists = Subcategory::where('business_id', $activeBusinessId)->where('id', $request->subcategory_id)->exists();
            if (!$subExists) {
                return response()->json(['message' => 'Invalid subcategory specified for active business.'], 422);
            }
        }

        $imagePath = null;
        try {
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $path = $request->file('image')->store('uploads', 'public');
                $imagePath = asset('storage/' . $path);
            } else if ($request->filled('image_base64')) {
                $base64Data = $request->image_base64;
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                    $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                    $type = strtolower($type[1]);
                    $base64Data = base64_decode($base64Data);
                    $filename = 'img_' . uniqid() . '.' . $type;
                    \Storage::disk('public')->put('uploads/' . $filename, $base64Data);
                    $imagePath = asset('storage/uploads/' . $filename);
                }
            } else if ($request->filled('image_url') && !empty($request->image_url)) {
                $imagePath = $request->image_url;
            } else if ($request->filled('image') && is_string($request->image) && !empty($request->image)) {
                $imagePath = $request->image;
            } else {
                $imagePath = $this->getUnsplashFallback($request->name);
            }
        } catch (\Exception $e) {
            $imagePath = $this->getUnsplashFallback($request->name);
        }

        $brandId = $request->filled('brand_id') && is_numeric($request->brand_id) ? intval($request->brand_id) : null;
        if (empty($brandId) && $request->filled('brand')) {
            $brandName = trim($request->brand);
            $brand = Brand::firstOrCreate([
                'business_id' => $activeBusinessId,
                'name' => $brandName
            ]);
            $brandId = $brand->id;
        }

        $categoryId = null;
        if ($request->filled('category_id') && is_numeric($request->category_id)) {
            $categoryId = intval($request->category_id);
        } else if ($request->filled('category') || $request->filled('category_id')) {
            $catName = trim($request->category ?: $request->category_id);
            if ($catName !== '') {
                $cat = Category::where('business_id', $activeBusinessId)->whereRaw('LOWER(name) = ?', [strtolower($catName)])->first();
                if (!$cat) {
                    $cat = Category::create([
                        'business_id' => $activeBusinessId,
                        'name' => $catName
                    ]);
                }
                $categoryId = $cat->id;
            }
        }

        $subcategoryId = $request->filled('subcategory_id') && is_numeric($request->subcategory_id) ? intval($request->subcategory_id) : null;
        if (empty($subcategoryId) && $request->filled('subcategory')) {
            $sub = Subcategory::firstOrCreate([
                'business_id' => $activeBusinessId,
                'category_id' => $categoryId,
                'name' => trim($request->subcategory)
            ]);
            $subcategoryId = $sub->id;
        }

        $product = Product::create([
            'business_id' => $activeBusinessId,
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'subcategory_id' => $subcategoryId,
            'name' => $request->name,
            'sku' => $request->sku,
            'barcode' => $request->barcode,
            'cost_price' => $request->cost_price,
            'selling_price' => $request->selling_price,
            'stock' => $request->stock,
            'min_stock' => $request->min_stock ?? 0,
            'expected_sell_date' => $request->expected_sell_date,
            'unit' => $request->unit ?? 'pcs',
            'description' => $request->description,
            'image' => $imagePath,
            'is_active' => $request->is_active !== false && $request->is_active !== '0',
            'has_variations' => $request->has_variations ?? false,
            'attributes' => $request->attributes ? json_encode($request->attributes) : null,
            'variations' => $request->variations ? json_encode($request->variations) : null
        ]);

        return response()->json($product->load(['category', 'brand', 'subcategory']), 201);
    }

    public function show(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('view products')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $product = Product::with(['category', 'brand', 'subcategory'])->findOrFail($id);
        return response()->json($product);
    }

    public function update(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('manage products')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $activeBusinessId = ResolveActiveBusiness::requireActiveBusinessId();
        $product = Product::where('business_id', $activeBusinessId)->findOrFail($id);

        $request->validate([
            'category_id' => 'nullable',
            'brand_id' => 'nullable',
            'subcategory_id' => 'nullable',
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($product->id)->where(function ($query) use ($activeBusinessId) {
                    return $query->where('business_id', $activeBusinessId);
                })
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->ignore($product->id)->where(function ($query) use ($activeBusinessId) {
                    return $query->where('business_id', $activeBusinessId);
                })
            ],
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
            'image' => 'nullable'
        ]);

        // Cross-tenant relationship validation
        if ($request->filled('category_id') && is_numeric($request->category_id)) {
            $catExists = Category::where('business_id', $activeBusinessId)->where('id', $request->category_id)->exists();
            if (!$catExists) {
                return response()->json(['message' => 'Invalid category specified for active business.'], 422);
            }
        }
        if ($request->filled('brand_id') && is_numeric($request->brand_id)) {
            $brandExists = Brand::where('business_id', $activeBusinessId)->where('id', $request->brand_id)->exists();
            if (!$brandExists) {
                return response()->json(['message' => 'Invalid brand specified for active business.'], 422);
            }
        }
        if ($request->filled('subcategory_id') && is_numeric($request->subcategory_id)) {
            $subExists = Subcategory::where('business_id', $activeBusinessId)->where('id', $request->subcategory_id)->exists();
            if (!$subExists) {
                return response()->json(['message' => 'Invalid subcategory specified for active business.'], 422);
            }
        }

        $data = $request->only([
            'category_id', 'brand_id', 'subcategory_id', 'name', 'sku', 'barcode', 'cost_price', 'selling_price',
            'expected_sell_date', 'unit', 'description', 'has_variations'
        ]);
        unset($data['stock'], $data['min_stock']);


        if ($request->has('category') || $request->has('category_id')) {
            if ($request->filled('category_id') && is_numeric($request->category_id)) {
                $data['category_id'] = intval($request->category_id);
            } else if ($request->filled('category') || $request->filled('category_id')) {
                $catName = trim($request->category ?: $request->category_id);
                if ($catName !== '') {
                    $cat = Category::where('business_id', $activeBusinessId)->whereRaw('LOWER(name) = ?', [strtolower($catName)])->first();
                    if (!$cat) {
                        $cat = Category::create([
                            'business_id' => $activeBusinessId,
                            'name' => $catName
                        ]);
                    }
                    $data['category_id'] = $cat->id;
                } else {
                    $data['category_id'] = null;
                }
            }
        }

        if ($request->has('brand')) {
            if ($request->filled('brand')) {
                $brandName = trim($request->brand);
                $brand = Brand::firstOrCreate([
                    'business_id' => $activeBusinessId,
                    'name' => $brandName
                ]);
                $data['brand_id'] = $brand->id;
            } else {
                $data['brand_id'] = null;
            }
        }
        
        if ($request->has('is_active')) {
            $data['is_active'] = $request->is_active !== false && $request->is_active !== '0';
        }

        try {
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $path = $request->file('image')->store('uploads', 'public');
                $data['image'] = asset('storage/' . $path);
            } else if ($request->filled('image_url') && !empty($request->image_url)) {
                $data['image'] = $request->image_url;
            } else if ($request->filled('image') && is_string($request->image) && !empty($request->image)) {
                $data['image'] = $request->image;
            } else if (empty($product->image) && !$request->hasFile('image')) {
                $data['image'] = $this->getUnsplashFallback($request->name ?? $product->name);
            }
        } catch (\Exception $e) {
            if (empty($product->image)) {
                $data['image'] = $this->getUnsplashFallback($request->name ?? $product->name);
            }
        }

        if ($request->has('attributes')) {
            $data['attributes'] = $request->attributes ? json_encode($request->attributes) : null;
        }
        if ($request->has('variations')) {
            $data['variations'] = $request->variations ? json_encode($request->variations) : null;
        }

        $oldCostPrice = (float) $product->cost_price;
        $oldSellingPrice = (float) $product->selling_price;
        $oldIsActive = (bool) $product->is_active;

        $product->update($data);

        $costChanged = array_key_exists('cost_price', $data) && (float)$data['cost_price'] !== $oldCostPrice;
        $priceChanged = array_key_exists('selling_price', $data) && (float)$data['selling_price'] !== $oldSellingPrice;
        $activeChanged = array_key_exists('is_active', $data) && (bool)$data['is_active'] !== $oldIsActive;

        if ($costChanged || $priceChanged || $activeChanged) {
            $oldValues = [];
            $newValues = [];
            if ($costChanged) {
                $oldValues['cost_price'] = $oldCostPrice;
                $newValues['cost_price'] = (float)$product->cost_price;
            }
            if ($priceChanged) {
                $oldValues['selling_price'] = $oldSellingPrice;
                $newValues['selling_price'] = (float)$product->selling_price;
            }
            if ($activeChanged) {
                $oldValues['is_active'] = $oldIsActive;
                $newValues['is_active'] = (bool)$product->is_active;
            }

            AuditService::logMutation(
                'governance',
                'updated',
                "Product {$product->name} pricing or status updated",
                $product,
                $oldValues,
                $newValues,
                ['product_id' => $product->id, 'sku' => $product->sku],
                null,
                $request->user()
            );
        }

        return response()->json($product->load(['category', 'brand', 'subcategory']));
    }

    public function destroy(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('manage products')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $activeBusinessId = ResolveActiveBusiness::requireActiveBusinessId();
        $product = Product::where('business_id', $activeBusinessId)->findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
