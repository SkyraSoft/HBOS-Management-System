<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::where('business_id', $request->user()->business_id)->get();
        return response()->json($customers);
    }

    public function store(Request $request)
    {
        \Log::info('Adding customer request payload: ', $request->all());

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'balance' => 'nullable|numeric'
        ]);
        
        \Log::info('Validation passed');

        $customer = Customer::create([
            'business_id' => $request->user()->business_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'balance' => $request->balance ?? 0
        ]);

        return response()->json($customer, 201);
    }

    public function show(Request $request, string $id)
    {
        $customer = Customer::where('business_id', $request->user()->business_id)->findOrFail($id);
        return response()->json($customer);
    }

    public function update(Request $request, string $id)
    {
        $customer = Customer::where('business_id', $request->user()->business_id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'balance' => 'nullable|numeric'
        ]);

        $customer->update($request->only(['name', 'phone', 'email', 'address', 'balance']));

        return response()->json($customer);
    }

    public function destroy(Request $request, string $id)
    {
        $customer = Customer::where('business_id', $request->user()->business_id)->findOrFail($id);
        $customer->delete();

        return response()->json(['message' => 'Customer deleted']);
    }
}
