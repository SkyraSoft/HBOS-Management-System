<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::where('business_id', $request->user()->business_id)->get();
        return response()->json($suppliers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'balance' => 'nullable|numeric'
        ]);

        $code = $request->code;
        if (empty($code)) {
            $latest = Supplier::where('business_id', $request->user()->business_id)->latest('id')->first();
            $nextNum = $latest ? ($latest->id + 1) : 1;
            $code = 'SUP-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        }

        $supplier = Supplier::create([
            'business_id' => $request->user()->business_id,
            'name' => $request->name,
            'code' => $code,
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'balance' => $request->balance ?? 0
        ]);

        return response()->json($supplier, 201);
    }

    public function show(Request $request, string $id)
    {
        $supplier = Supplier::where('business_id', $request->user()->business_id)->findOrFail($id);
        return response()->json($supplier);
    }

    public function update(Request $request, string $id)
    {
        $supplier = Supplier::where('business_id', $request->user()->business_id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'balance' => 'nullable|numeric'
        ]);

        $supplier->update($request->only(['name', 'code', 'contact_person', 'phone', 'email', 'address', 'balance']));

        return response()->json($supplier);
    }

    public function destroy(Request $request, string $id)
    {
        $supplier = Supplier::where('business_id', $request->user()->business_id)->findOrFail($id);
        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted']);
    }
}
