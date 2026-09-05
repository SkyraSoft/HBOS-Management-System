<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user || !$user->business) {
            return response()->json(['data' => []]);
        }

        $employees = Employee::where('business_id', $user->business->id)->get();
        return response()->json(['data' => $employees]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->business) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'salary_amount' => 'required|numeric|min:0',
            'payment_cycle' => 'required|in:daily,weekly,monthly',
            'next_payment_date' => 'nullable|date',
            'is_active' => 'boolean'
        ]);

        $employee = $user->business->employees()->create($validated);

        return response()->json(['message' => 'Employee created successfully.', 'data' => $employee]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->business) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee = $user->business->employees()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'role' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'salary_amount' => 'sometimes|required|numeric|min:0',
            'payment_cycle' => 'sometimes|required|in:daily,weekly,monthly',
            'next_payment_date' => 'nullable|date',
            'is_active' => 'boolean'
        ]);

        $employee->update($validated);

        return response()->json(['message' => 'Employee updated successfully.', 'data' => $employee]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || !$user->business) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee = $user->business->employees()->findOrFail($id);
        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully.']);
    }
}
