<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KhataTransaction;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class KhataController extends Controller
{
    public function index(Request $request)
    {
        $transactions = KhataTransaction::with('customer')->where('business_id', $request->user()->business_id)->orderBy('date', 'desc')->get();
        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'type' => 'required|in:give,got',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $customer = Customer::where('business_id', $request->user()->business_id)->findOrFail($request->customer_id);

            $transaction = KhataTransaction::create([
                'business_id' => $request->user()->business_id,
                'customer_id' => $request->customer_id,
                'type' => $request->type,
                'amount' => $request->amount,
                'date' => $request->date,
                'notes' => $request->notes
            ]);

            // Update balance
            if ($request->type === 'give') {
                // Gave credit -> balance goes up
                $customer->increment('balance', $request->amount);
            } else {
                // Got payment -> balance goes down
                $customer->decrement('balance', $request->amount);
            }

            DB::commit();

            return response()->json($transaction, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(Request $request, string $id)
    {
        $transaction = KhataTransaction::where('business_id', $request->user()->business_id)->findOrFail($id);

        try {
            DB::beginTransaction();

            $customer = Customer::where('business_id', $request->user()->business_id)->findOrFail($transaction->customer_id);

            // Reverse balance
            if ($transaction->type === 'give') {
                $customer->decrement('balance', $transaction->amount);
            } else {
                $customer->increment('balance', $transaction->amount);
            }

            $transaction->delete();

            DB::commit();

            return response()->json(['message' => 'Transaction deleted and balance reverted']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
