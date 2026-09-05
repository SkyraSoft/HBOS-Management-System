<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\SystemDailyCheck;
use Illuminate\Support\Facades\Log;

class DailySystemCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-system-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches the SystemDailyCheck event to trigger all daily background checks (inventory, expenses, salaries)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Daily System Check...');
        Log::info('DailySystemCheck command triggered.');
        
        event(new SystemDailyCheck());
        
        $this->info('SystemDailyCheck event dispatched successfully.');
        Log::info('DailySystemCheck command finished.');
    }
}
