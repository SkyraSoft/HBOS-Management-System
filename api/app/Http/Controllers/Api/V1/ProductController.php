<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Subcategory;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    protected function canViewCosts($user): bool
    {
        if (!$user) return false;
        if ($user->hasRole('Business Owner')) return true;
        try {
            if ($user->hasPermissionTo('view product costs')) return true;
        } catch (\Throwable $e) {
            return false;
        }
        return false;
    }

    protected function formatProduct($product, $user): array
    {
        $arr = $product->toArray();
        $canCost = $this->canViewCosts($user);

        if (!$canCost) {
            unset($arr['cost_price'], $arr['cost']);
        }

        $altBarcodes = [];
        if ($product->relationLoaded('barcodes')) {
            $altBarcodes = $product->barcodes->where('barcode_type', 'ALTERNATE')->pluck('barcode')->values()->toArray();
        } else {
            $altBarcodes = ProductBarcode::where('product_id', $product->id)
                ->where('barcode_type', 'ALTERNATE')
                ->pluck('barcode')
                ->toArray();
        }

        $arr['alternate_barcodes'] = $altBarcodes;
        $arr['primary_barcode'] = $product->barcode ?? null;

        if ($canCost) {
            $sellingPrice = floatval($product->selling_price ?? 0);
            $costPrice = floatval($product->cost_price ?? 0);
            $grossMarginAmount = round($sellingPrice - $costPrice, 2);
            $grossMarginPercent = $sellingPrice > 0 ? round(($grossMarginAmount / $sellingPrice) * 100, 1) : 0.0;
            $arr['gross_margin_amount'] = $grossMarginAmount;
            $arr['gross_margin_percentage'] = $grossMarginPercent;
        }

        return $arr;
    }

    protected function syncBarcodes(Product $product, int $businessId, ?string $primaryBarcode, ?array $altBarcodes): void
    {
        $primaryBarcode = !is_null($primaryBarcode) ? trim((string)$primaryBarcode) : null;
        if ($primaryBarcode === '') $primaryBarcode = null;

        if ($primaryBarcode !== null) {
            $collision = ProductBarcode::where('business_id', $businessId)
                ->where('barcode', $primaryBarcode)
                ->where('product_id', '!=', $product->id)
                ->first();
            if ($collision) {
                throw ValidationException::withMessages([
                    'barcode' => ["Barcode '{$primaryBarcode}' is already assigned to another product in this business."]
                ]);
            }

            // Clean up any other PRIMARY barcode for this product if barcode changed
            ProductBarcode::where('business_id', $businessId)
                ->where('product_id', $product->id)
                ->where('barcode_type', 'PRIMARY')
                ->where('barcode', '!=', $primaryBarcode)
                ->delete();

            ProductBarcode::updateOrCreate(
                ['business_id' => $businessId, 'product_id' => $product->id, 'barcode_type' => 'PRIMARY'],
                ['barcode' => $primaryBarcode]
            );
        } else {
            ProductBarcode::where('business_id', $businessId)
                ->where('product_id', $product->id)
                ->where('barcode_type', 'PRIMARY')
                ->delete();
        }

        if ($altBarcodes !== null) {
            $cleaned = [];
            foreach ($altBarcodes as $b) {
                $trimmed = trim((string)$b);
                if ($trimmed !== '') {
                    if ($primaryBarcode !== null && $trimmed === $primaryBarcode) {
                        throw ValidationException::withMessages([
                            'alternate_barcodes' => ["Alternate barcode '{$trimmed}' cannot be identical to primary barcode."]
                        ]);
                    }
                    if (in_array($trimmed, $cleaned)) {
                        throw ValidationException::withMessages([
                            'alternate_barcodes' => ["Duplicate alternate barcode '{$trimmed}' provided in request."]
                        ]);
                    }
                    $cleaned[] = $trimmed;
                }
            }

            if (!empty($cleaned)) {
                $conflict = ProductBarcode::where('business_id', $businessId)
                    ->whereIn('barcode', $cleaned)
                    ->where('product_id', '!=', $product->id)
                    ->first();
                if ($conflict) {
                    throw ValidationException::withMessages([
                        'alternate_barcodes' => ["Barcode '{$conflict->barcode}' is already assigned to another product in this business."]
                    ]);
                }
            }

            ProductBarcode::where('business_id', $businessId)
                ->where('product_id', $product->id)
                ->where('barcode_type', 'ALTERNATE')
                ->whereNotIn('barcode', $cleaned)
                ->delete();

            foreach ($cleaned as $altB) {
                ProductBarcode::firstOrCreate([
                    'business_id' => $businessId,
                    'product_id' => $product->id,
                    'barcode' => $altB,
                    'barcode_type' => 'ALTERNATE',
                ]);
            }
        }
    }

    public function index(Request $request)
    {
        if (!$request->user()->hasPermissionTo('view products')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $query = Product::with(['category', 'brand', 'subcategory', 'barcodes'])
            ->where('business_id', $businessId);

        if ($request->has('search') && !empty($request->search)) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhereHas('barcodes', function ($bQ) use ($search) {
                      $bQ->where('barcode', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('barcode') && !empty($request->barcode)) {
            $barcode = trim($request->barcode);
            $query->where(function ($q) use ($barcode) {
                $q->where('barcode', $barcode)
                  ->orWhereHas('barcodes', function ($bQ) use ($barcode) {
                      $bQ->where('barcode', $barcode);
                  });
            });
        }

        if ($request->has('is_active') || $request->has('active')) {
            $activeVal = $request->input('is_active', $request->input('active'));
            $query->where('is_active', filter_var($activeVal, FILTER_VALIDATE_BOOLEAN));
        }

        $products = $query->get()->map(fn($p) => $this->formatProduct($p, $request->user()));
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

        if ($request->has('alternate_barcodes') && is_string($request->alternate_barcodes)) {
            $decoded = json_decode($request->alternate_barcodes, true);
            if (is_array($decoded)) {
                $request->merge(['alternate_barcodes' => $decoded]);
            }
        }

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
            'wholesale_price' => 'nullable|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'allow_fractional' => 'nullable|boolean',
            'sale_unit' => 'nullable|string|max:20',
            'conversion_factor' => 'nullable|numeric|min:0.001',
            'unit' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
            'image' => 'nullable',
            'has_variations' => 'nullable|boolean',
            'attributes' => 'nullable|array',
            'variations' => 'nullable|array',
            'alternate_barcodes' => 'nullable|array',
            'alternate_barcodes.*' => 'string|max:100',
        ]);

        // Below-cost wholesale pricing policy enforcement
        if ($request->filled('wholesale_price') && (float)$request->wholesale_price > 0 && (float)$request->wholesale_price < (float)$request->cost_price) {
            if (!$request->user()->hasRole('Business Owner')) {
                return response()->json(['message' => 'Configuring wholesale price below cost price requires Business Owner authorization.'], 403);
            }
        }

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
            $storageDir = 'businesses/' . $activeBusinessId . '/products';
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $path = $request->file('image')->store($storageDir, 'public');
                $imagePath = asset('storage/' . $path);
            } else if ($request->filled('image_base64')) {
                $base64Data = $request->image_base64;
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                    $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                    $type = strtolower($type[1]);
                    $base64Data = base64_decode($base64Data);
                    $filename = 'img_' . uniqid() . '.' . $type;
                    \Storage::disk('public')->put($storageDir . '/' . $filename, $base64Data);
                    $imagePath = asset('storage/' . $storageDir . '/' . $filename);
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

        return DB::transaction(function () use ($request, $activeBusinessId, $categoryId, $brandId, $subcategoryId, $imagePath) {
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
                'wholesale_price' => $request->wholesale_price ?? null,
                'stock' => $request->stock,
                'min_stock' => $request->min_stock ?? 0,
                'allow_fractional' => $request->has('allow_fractional') ? filter_var($request->allow_fractional, FILTER_VALIDATE_BOOLEAN) : false,
                'sale_unit' => $request->sale_unit ?? null,
                'conversion_factor' => $request->conversion_factor ?? 1.000,
                'expected_sell_date' => $request->expected_sell_date,
                'unit' => $request->unit ?? 'pcs',
                'description' => $request->description,
                'image' => $imagePath,
                'is_active' => $request->has('is_active') ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : true,
                'has_variations' => $request->has_variations ?? false,
                'attributes' => $request->attributes ? json_encode($request->attributes) : null,
                'variations' => $request->variations ? json_encode($request->variations) : null
            ]);

            // Provision BranchInventory and initial opening stock movement
            $targetBranchId = $request->input('branch_id')
                ?? $request->user()->branch_id
                ?? \App\Models\Branch::where('business_id', $activeBusinessId)->value('id');

            if ($targetBranchId) {
                \App\Models\BranchInventory::create([
                    'business_id' => $activeBusinessId,
                    'branch_id' => $targetBranchId,
                    'product_id' => $product->id,
                    'quantity_on_hand' => (float)$request->stock,
                    'minimum_stock' => $product->min_stock ?? 0.000,
                ]);

                if ((float)$request->stock > 0) {
                    \App\Models\InventoryMovement::create([
                        'business_id' => $activeBusinessId,
                        'branch_id' => $targetBranchId,
                        'product_id' => $product->id,
                        'type' => 'adjustment',
                        'quantity' => (float)$request->stock,
                        'unit_cost' => $product->cost_price ?? 0,
                        'reference_type' => 'opening_stock',
                        'reference_id' => $product->id,
                        'performed_by' => $request->user()->id,
                        'balance' => (float)$request->stock,
                        'notes' => 'Initial product opening stock',
                    ]);
                }
            }

            // Sync canonical product_barcodes table
            $this->syncBarcodes($product, $activeBusinessId, $request->barcode, $request->input('alternate_barcodes'));

            return response()->json($this->formatProduct($product->load(['category', 'brand', 'subcategory', 'barcodes']), $request->user()), 201);
        });
    }

    public function show(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('view products')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $activeBusinessId = ResolveActiveBusiness::requireActiveBusinessId();
        $product = Product::with(['category', 'brand', 'subcategory', 'barcodes'])
            ->where('business_id', $activeBusinessId)
            ->findOrFail($id);

        return response()->json($this->formatProduct($product, $request->user()));
    }

    public function update(Request $request, string $id)
    {
        if (!$request->user()->hasPermissionTo('manage products')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $activeBusinessId = ResolveActiveBusiness::requireActiveBusinessId();
        $product = Product::where('business_id', $activeBusinessId)->findOrFail($id);

        if ($request->has('alternate_barcodes') && is_string($request->alternate_barcodes)) {
            $decoded = json_decode($request->alternate_barcodes, true);
            if (is_array($decoded)) {
                $request->merge(['alternate_barcodes' => $decoded]);
            }
        }

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
            'wholesale_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'allow_fractional' => 'nullable|boolean',
            'sale_unit' => 'nullable|string|max:20',
            'conversion_factor' => 'nullable|numeric|min:0.001',
            'unit' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
            'image' => 'nullable',
            'alternate_barcodes' => 'nullable|array',
            'alternate_barcodes.*' => 'string|max:100',
        ]);

        if ($request->filled('wholesale_price') && (float)$request->wholesale_price > 0 && (float)$request->wholesale_price < (float)$request->cost_price) {
            if (!$request->user()->hasRole('Business Owner')) {
                return response()->json(['message' => 'Configuring wholesale price below cost price requires Business Owner authorization.'], 403);
            }
        }

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
            'wholesale_price', 'expected_sell_date', 'unit', 'description', 'has_variations',
            'allow_fractional', 'sale_unit', 'conversion_factor'
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
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->has('allow_fractional')) {
            $data['allow_fractional'] = filter_var($request->allow_fractional, FILTER_VALIDATE_BOOLEAN);
        }

        try {
            $storageDir = 'businesses/' . $activeBusinessId . '/products';
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $path = $request->file('image')->store($storageDir, 'public');
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
        $oldWholesalePrice = (float) ($product->wholesale_price ?? 0);
        $oldBarcode = (string) ($product->barcode ?? '');
        $oldIsActive = (bool) $product->is_active;

        return DB::transaction(function () use (
            $product, $data, $request, $activeBusinessId,
            $oldCostPrice, $oldSellingPrice, $oldWholesalePrice, $oldBarcode, $oldIsActive
        ) {
            $lockedProduct = Product::where('business_id', $activeBusinessId)
                ->where('id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedProduct->update($data);

            // Sync canonical barcodes in product_barcodes table
            $altBarcodes = $request->has('alternate_barcodes') ? $request->input('alternate_barcodes') : null;
            $this->syncBarcodes($lockedProduct, $activeBusinessId, $lockedProduct->barcode, $altBarcodes);

            $costChanged = array_key_exists('cost_price', $data) && (float)$data['cost_price'] !== $oldCostPrice;
            $priceChanged = array_key_exists('selling_price', $data) && (float)$data['selling_price'] !== $oldSellingPrice;
            $wholesaleChanged = array_key_exists('wholesale_price', $data) && (float)($data['wholesale_price'] ?? 0) !== $oldWholesalePrice;
            $barcodeChanged = array_key_exists('barcode', $data) && (string)($data['barcode'] ?? '') !== $oldBarcode;
            $activeChanged = array_key_exists('is_active', $data) && (bool)$data['is_active'] !== $oldIsActive;

            if ($costChanged || $priceChanged || $wholesaleChanged || $barcodeChanged || $activeChanged) {
                $oldValues = [];
                $newValues = [];
                if ($costChanged) {
                    $oldValues['cost_price'] = $oldCostPrice;
                    $newValues['cost_price'] = (float)$lockedProduct->cost_price;
                }
                if ($priceChanged) {
                    $oldValues['selling_price'] = $oldSellingPrice;
                    $newValues['selling_price'] = (float)$lockedProduct->selling_price;
                }
                if ($wholesaleChanged) {
                    $oldValues['wholesale_price'] = $oldWholesalePrice;
                    $newValues['wholesale_price'] = (float)($lockedProduct->wholesale_price ?? 0);
                }
                if ($barcodeChanged) {
                    $oldValues['barcode'] = $oldBarcode;
                    $newValues['barcode'] = (string)($lockedProduct->barcode ?? '');
                }
                if ($activeChanged) {
                    $oldValues['is_active'] = $oldIsActive;
                    $newValues['is_active'] = (bool)$lockedProduct->is_active;
                }

                AuditService::logMutation(
                    'governance',
                    'updated',
                    "Product {$lockedProduct->name} pricing or barcode updated",
                    $lockedProduct,
                    $oldValues,
                    $newValues,
                    ['product_id' => $lockedProduct->id, 'sku' => $lockedProduct->sku],
                    null,
                    $request->user()
                );
            }

            return response()->json($this->formatProduct($lockedProduct->fresh(['category', 'brand', 'subcategory', 'barcodes']), $request->user()));
        });
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
