<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AddMonthlyLeaveBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:add-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add 1 day to annual leave balance for all active employees every month';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \App\Models\Employee::where('status', 'active')->increment('annual_leave_balance', 1);
        $this->info('Added 1 day of annual leave to all active employees.');
        return Command::SUCCESS;
    }
}
