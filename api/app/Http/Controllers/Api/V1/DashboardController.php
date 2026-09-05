<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Customer;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $businessId = $request->user()->business_id;

        $startDateInput = $request->query('start_date');
        $endDateInput = $request->query('end_date');

        if ($startDateInput && $endDateInput) {
            $startDate = Carbon::parse($startDateInput)->startOfDay();
            $endDate = Carbon::parse($endDateInput)->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }

        // Sums filtered by date range
        $salesTotal = Sale::where('business_id', $businessId)->whereBetween('date', [$startDate, $endDate])->sum('total');
        $purchasesTotal = Purchase::where('business_id', $businessId)->whereBetween('date', [$startDate, $endDate])->sum('total');
        $expensesTotal = Expense::where('business_id', $businessId)->whereBetween('date', [$startDate, $endDate])->sum('amount');
        $customerCount = Customer::where('business_id', $businessId)->whereBetween('created_at', [$startDate, $endDate])->count();

        $revenue = $salesTotal - $expensesTotal - $purchasesTotal;

        // Grouping for chart
        $diffInDays = $startDate->diffInDays($endDate);
        
        if ($diffInDays <= 31) {
            // Group by day
            $salesGrouped = Sale::where('business_id', $businessId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('DATE(date) as label, SUM(total) as total')
                ->groupBy('label')
                ->orderBy('label')
                ->get();

            $data = [];
            $labels = [];
            $salesMap = $salesGrouped->pluck('total', 'label')->toArray();
            $current = clone $startDate;
            while ($current <= $endDate) {
                $label = $current->format('Y-m-d');
                $labels[] = $current->format('M d');
                $data[] = floatval($salesMap[$label] ?? 0);
                $current->addDay();
            }
        } else {
            // Group by month
            $salesGrouped = Sale::where('business_id', $businessId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw("DATE_FORMAT(date, '%Y-%m') as label, SUM(total) as total")
                ->groupBy('label')
                ->orderBy('label')
                ->get();

            $data = [];
            $labels = [];
            $salesMap = $salesGrouped->pluck('total', 'label')->toArray();
            $current = clone $startDate;
            while ($current <= $endDate) {
                $label = $current->format('Y-m');
                $labels[] = $current->format('M Y');
                $data[] = floatval($salesMap[$label] ?? 0);
                $current->addMonth();
            }
        }

        return response()->json([
            'sales_total' => floatval($salesTotal),
            'purchases_total' => floatval($purchasesTotal),
            'expenses_total' => floatval($expensesTotal),
            'customer_count' => intval($customerCount),
            'net_revenue' => floatval($revenue),
            'chart_data' => [
                'labels' => $labels,
                'data' => $data
            ]
        ]);
    }
}
