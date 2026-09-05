<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = Expense::where('business_id', $request->user()->business_id)->orderBy('date', 'desc')->get();
        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        $expense = Expense::create([
            'business_id' => $request->user()->business_id,
            'category' => $request->category,
            'amount' => $request->amount,
            'date' => $request->date,
            'description' => $request->description
        ]);

        return response()->json($expense, 201);
    }

    public function show(Request $request, string $id)
    {
        $expense = Expense::where('business_id', $request->user()->business_id)->findOrFail($id);
        return response()->json($expense);
    }

    public function update(Request $request, string $id)
    {
        $expense = Expense::where('business_id', $request->user()->business_id)->findOrFail($id);

        $request->validate([
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        $expense->update($request->only(['category', 'amount', 'date', 'description']));

        return response()->json($expense);
    }

    public function destroy(Request $request, string $id)
    {
        $expense = Expense::where('business_id', $request->user()->business_id)->findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Expense deleted']);
    }
}
