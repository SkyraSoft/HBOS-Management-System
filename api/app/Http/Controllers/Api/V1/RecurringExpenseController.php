<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RecurringExpense;
use Illuminate\Http\Request;

class RecurringExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = RecurringExpense::where('business_id', $request->user()->business_id)->orderBy('next_due_date', 'asc')->get();
        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'frequency' => 'required|string|max:255',
            'next_due_date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        $expense = RecurringExpense::create([
            'business_id' => $request->user()->business_id,
            'category' => $request->category,
            'amount' => $request->amount,
            'frequency' => $request->frequency,
            'next_due_date' => $request->next_due_date,
            'description' => $request->description
        ]);

        return response()->json($expense, 201);
    }

    public function show(Request $request, string $id)
    {
        $expense = RecurringExpense::where('business_id', $request->user()->business_id)->findOrFail($id);
        return response()->json($expense);
    }

    public function update(Request $request, string $id)
    {
        $expense = RecurringExpense::where('business_id', $request->user()->business_id)->findOrFail($id);

        $request->validate([
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'frequency' => 'required|string|max:255',
            'next_due_date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        $expense->update($request->only(['category', 'amount', 'frequency', 'next_due_date', 'description']));

        return response()->json($expense);
    }

    public function destroy(Request $request, string $id)
    {
        $expense = RecurringExpense::where('business_id', $request->user()->business_id)->findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Recurring expense deleted']);
    }
}
