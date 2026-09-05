<?php

namespace App\Listeners;

use App\Events\SystemDailyCheck;
use App\Models\Employee;
use App\Models\Business;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckSalaryReminders
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
        Log::info("Starting CheckSalaryReminders...");

        $businesses = Business::all();

        foreach ($businesses as $business) {
            $upcomingSalaries = Employee::where('business_id', $business->id)
                ->where('is_active', true)
                ->whereNotNull('next_payment_date')
                ->get();

            foreach ($upcomingSalaries as $employee) {
                $dueDate = Carbon::parse($employee->next_payment_date);
                $daysUntilDue = now()->diffInDays($dueDate, false);

                // If due within 3 days or overdue
                if ($daysUntilDue <= 3) {
                    
                    $priority = $daysUntilDue < 0 ? 'Emergency/Critical' : 'Important/Soon';
                    $colorClass = $daysUntilDue < 0 ? 'text-danger' : 'text-warning';
                    $bgClass = $daysUntilDue < 0 ? 'bg-danger-subtle' : 'bg-warning-subtle';
                    $icon = 'bi-cash-coin';

                    $message = $daysUntilDue < 0 
                        ? "Salary for '{$employee->name}' (Amount: {$employee->salary_amount}) is OVERDUE by " . abs($daysUntilDue) . " days."
                        : "Salary for '{$employee->name}' (Amount: {$employee->salary_amount}) is due in {$daysUntilDue} days on {$dueDate->format('Y-m-d')}.";

                    if ($daysUntilDue == 0) {
                        $message = "Salary for '{$employee->name}' (Amount: {$employee->salary_amount}) is due TODAY.";
                    }

                    $business->notifications()->create([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'type' => 'App\Notifications\SalaryDueNotification',
                        'data' => [
                            'title' => 'Upcoming Salary Payment',
                            'message' => $message,
                            'type' => $priority,
                            'action_type' => 'PAY_SALARY',
                            'action_payload' => ['employee_id' => $employee->id, 'amount' => $employee->salary_amount],
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
