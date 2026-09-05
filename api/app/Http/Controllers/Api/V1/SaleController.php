<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class SaleController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $businessId = $request->user() ? $request->user()->business_id : null;
        $query = Sale::with('items.product')->orderBy('created_at', 'desc');
        if ($businessId) {
            $query->where('business_id', $businessId);
        }
        $sales = $query->get();
        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable',
            'invoice_number' => 'nullable|string',
            'date' => 'nullable',
            'subtotal' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'total' => 'required|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric',
            'items.*.discount' => 'nullable|numeric',
            'items.*.total' => 'required|numeric'
        ]);

        try {
            DB::beginTransaction();

            $businessId = $request->user() ? $request->user()->business_id : 1;
            $userId = $request->user() ? $request->user()->id : 1;
            
            // Unique invoice number generation fallback
            $invoiceNumber = $request->invoice_number;
            if (!$invoiceNumber || Sale::where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber = 'INV-' . date('Ymd') . '-' . rand(10000, 99999);
            }

            $subtotal = floatval($request->subtotal);
            $discount = floatval($request->discount ?? 0);
            $tax = floatval($request->tax ?? 0);
            $total = floatval($request->total);

            $sale = Sale::create([
                'business_id' => $businessId,
                'user_id' => $userId,
                'customer_id' => $request->customer_id ? intval($request->customer_id) : null,
                'invoice_number' => $invoiceNumber,
                'date' => $request->date ? date('Y-m-d', strtotime($request->date)) : date('Y-m-d'),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'payment_method' => $request->payment_method ?: 'Cash',
                'notes' => $request->notes
            ]);

            foreach ($request->items as $itemData) {
                $rawPId = strval($itemData['product_id']);
                $cleanPId = strpos($rawPId, '_') !== false ? explode('_', $rawPId)[0] : $rawPId;
                $pId = intval($cleanPId);

                $qty = intval($itemData['quantity']);
                $unitPrice = floatval($itemData['unit_price']);
                $itemTotal = floatval($itemData['total']);
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $pId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount' => floatval($itemData['discount'] ?? 0),
                    'total' => $itemTotal
                ]);

                // Safely decrement stock
                $this->stockService->decrementStock(
                    $pId, 
                    $qty, 
                    $businessId
                );
            }

            DB::commit();

            return response()->json($sale->load('items.product'), 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(Request $request, string $id)
    {
        $sale = Sale::with('items.product')->findOrFail($id);
        return response()->json($sale);
    }

    public function destroy(Request $request, string $id)
    {
        $sale = Sale::with('items')->findOrFail($id);
        $sale->items()->delete();
        $sale->delete();
        return response()->json(['message' => 'Sale deleted successfully']);
    }
}
