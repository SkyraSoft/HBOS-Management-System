<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $purchases = Purchase::with(['items.product', 'supplier'])->where('business_id', $request->user()->business_id)->orderBy('date', 'desc')->get();
        return response()->json($purchases);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'nullable|integer',
            'po_number' => 'required|string|unique:purchases',
            'date' => 'required|date',
            'subtotal' => 'required|numeric',
            'total' => 'required|numeric',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric',
            'items.*.total' => 'required|numeric'
        ]);

        try {
            DB::beginTransaction();

            $purchase = Purchase::create([
                'business_id' => $request->user()->business_id,
                'user_id' => $request->user()->id,
                'supplier_id' => $request->supplier_id,
                'po_number' => $request->po_number,
                'date' => $request->date,
                'subtotal' => $request->subtotal,
                'total' => $request->total,
                'status' => $request->status ?? 'received',
                'notes' => $request->notes
            ]);

            foreach ($request->items as $itemData) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'],
                    'total' => $itemData['total']
                ]);

                // Increase stock
                $this->stockService->incrementStock(
                    $itemData['product_id'], 
                    $itemData['quantity'], 
                    $request->user()->business_id
                );
            }

            DB::commit();

            return response()->json($purchase->load('items'), 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(Request $request, string $id)
    {
        $purchase = Purchase::with('items.product')->where('business_id', $request->user()->business_id)->findOrFail($id);
        return response()->json($purchase);
    }

    public function destroy(Request $request, string $id)
    {
        $purchase = Purchase::with('items')->where('business_id', $request->user()->business_id)->findOrFail($id);

        try {
            DB::beginTransaction();

            // Reverse stock
            foreach ($purchase->items as $item) {
                $this->stockService->decrementStock(
                    $item->product_id, 
                    $item->quantity, 
                    $request->user()->business_id
                );
            }

            $purchase->delete();

            DB::commit();

            return response()->json(['message' => 'Purchase deleted and stock reversed']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
