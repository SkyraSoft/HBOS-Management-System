<?php

namespace App\Listeners;

use App\Events\SystemDailyCheck;
use App\Models\RecurringExpense;
use App\Models\Business;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckExpenseReminders
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SystemDailyCheck $event): void
    {
        Log::info("Starting CheckExpenseReminders...");

        $businesses = Business::all();

        foreach ($businesses as $business) {
            // We need to check recurring expenses for this business
            // A recurring expense is upcoming if next_due_date is within 3 days
            $upcomingExpenses = RecurringExpense::where('business_id', $business->id)
                ->whereNotNull('next_due_date')
                ->get();

            foreach ($upcomingExpenses as $expense) {
                $dueDate = Carbon::parse($expense->next_due_date);
                $daysUntilDue = now()->diffInDays($dueDate, false); // false means negative if in past

                // If due within 3 days or overdue
                if ($daysUntilDue <= 3) {
                    
                    $priority = $daysUntilDue < 0 ? 'Emergency/Critical' : 'Important/Soon';
                    $colorClass = $daysUntilDue < 0 ? 'text-danger' : 'text-warning';
                    $bgClass = $daysUntilDue < 0 ? 'bg-danger-subtle' : 'bg-warning-subtle';
                    $icon = $daysUntilDue < 0 ? 'bi-exclamation-octagon-fill' : 'bi-calendar-event-fill';

                    $message = $daysUntilDue < 0 
                        ? "Expense '{$expense->name}' (Amount: {$expense->amount}) is OVERDUE by " . abs($daysUntilDue) . " days."
                        : "Expense '{$expense->name}' (Amount: {$expense->amount}) is due in {$daysUntilDue} days on {$dueDate->format('Y-m-d')}.";

                    if ($daysUntilDue == 0) {
                        $message = "Expense '{$expense->name}' (Amount: {$expense->amount}) is due TODAY.";
                    }

                    // To avoid spamming, we can check if a notification for this expense ID already exists
                    // For simplicity, we just generate it. 

                    $business->notifications()->create([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'type' => 'App\Notifications\ExpenseDueNotification',
                        'data' => [
                            'title' => 'Upcoming Expense',
                            'message' => $message,
                            'type' => $priority,
                            'action_type' => 'PAY_EXPENSE',
                            'action_payload' => ['expense_id' => $expense->id, 'amount' => $expense->amount],
                            'icon' => $icon,
                            'colorClass' => $colorClass,
                            'bgClass' => $bgClass,
                        ],
                    ]);
                }
            }
        }
    }
}
