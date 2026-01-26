<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EvaluateCustomerStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:evaluate-customer-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate and update customer status based on inactivity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Customer status evaluation job started');
        
        $cutoffDate = Carbon::now()->subDays(30);
        
        // Logic from Python:
        // Find customers whose *latest* wallet transaction is < cutoff_date
        // Or perhaps customers with NO transactions? Python logic uses having(max(created_at) < cutoff)
        
        $inactiveCustomerIds = Wallet::select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('MAX(created_at) < ?', [$cutoffDate])
            ->pluck('customer_id');
            
        if ($inactiveCustomerIds->isEmpty()) {
            $this->info('No inactive customers found.');
            return;
        }
        
        $count = Customer::whereIn('id', $inactiveCustomerIds)
            ->where('status', '!=', 'inactive') // Optimization: only update if not already inactive
            ->update(['status' => 'inactive']);
            
        $this->info("Customer status update completed. Updated {$count} customers to inactive.");
    }
}
