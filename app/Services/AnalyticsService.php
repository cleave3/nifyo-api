<?php

namespace App\Services;

use App\Models\Auth;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\AuthService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService extends AuthService
{
    // Note: Python inherits AuthService, though typically composition is preferred.
    // We'll mimic the inheritance or just use dependency injection if needed.
    // Given the Python code extends it, it might use its methods.

    public function getTotalUsers()
    {
        return Auth::count();
    }

    public function getTotalStaffs()
    {
        return Auth::where('user_type', 'staff')->count();
    }

    public function getTotalCustomers($status = null)
    {
        $query = Customer::query();
        if ($status) {
            $query->where('status', $status);
        }
        return $query->count();
    }

    public function getTotalBalances()
    {
        $credit = Wallet::sum('credit') ?? 0;
        $debit = Wallet::sum('debit') ?? 0;
        return $credit - $debit;
    }

    public function getTotalTransactionAmountByType($type, $month = null, $year = null)
    {
        $query = Transaction::where('transaction_type', $type)
                            ->where('status', 'completed');
        
        if ($year) {
            $query->whereYear('created_at', $year);
        }
        if ($month) {
            $query->whereMonth('created_at', $month);
        }
        
        return $query->sum('amount') ?? 0;
    }
    
    public function getTotalDueCollections()
    {
        return Customer::whereDate('next_payment_date', '<=', Carbon::today())->count();
    }
    
    public function getCustomersWithDueCollections()
    {
        return Customer::whereDate('next_payment_date', '<=', Carbon::today())->get();
    }
    
    public function getTotalTransactionsStatus($status = null)
    {
        $query = Transaction::query();
        if ($status) {
            $query->where('status', $status);
        }
        return $query->count();
    }

    public function getOverviewStats()
    {
        // Parallel execution isn't native/easy in PHP FPM without async libs, 
        // but for these simple queries sequential execution is fast enough.
        
        return [
            "total_users" => $this->getTotalUsers(),
            "total_staffs" => $this->getTotalStaffs(),
            "total_customers" => $this->getTotalCustomers(),
            "total_active_customers" => $this->getTotalCustomers('active'),
            "total_inactive_customers" => $this->getTotalCustomers('inactive'),
            "total_balances" => $this->getTotalBalances(),
            "total_due_collections" => $this->getTotalDueCollections(),
            "total_pending_transactions" => $this->getTotalTransactionsStatus('pending'),
            "total_completed_transactions" => $this->getTotalTransactionsStatus('completed'),
            "total_cancelled_transactions" => $this->getTotalTransactionsStatus('cancelled'),
            "total_customer_deposits" => $this->getTotalTransactionAmountByType('customer_deposit'),
            "total_payouts" => $this->getTotalTransactionAmountByType('payout'),
            "total_income" => $this->getTotalTransactionAmountByType('income'),
        ];
    }
    
    public function getTransactionsCountByInitiator($month, $year) 
    {
        $stats = Transaction::select(
                'initiator_id',
                DB::raw('count(*) as count'),
                DB::raw('sum(amount) as collection_volume')
            )
            ->where('status', 'completed')
            ->where('transaction_type', 'customer_deposit')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->groupBy('initiator_id')
            ->orderByDesc('collection_volume')
            ->get();        
            
        // Map names manually or via relationship
        $collectorsStats = $stats->map(function($stat) {
             // We can use Auth model relationship if defined, or just lookup
             $user = Auth::find($stat->initiator_id);
             return [
                 'collection_volume' => (float)$stat->collection_volume,
                 'initiator_id' => $stat->initiator_id,
                 'count' => $stat->count,
                 'name' => $user ? $user->name : 'Unknown' // Assuming name field exists or computed
             ];
        });
        
        return [
            "total_customer_deposits" => $this->getTotalTransactionAmountByType('customer_deposit', $month, $year),
            "total_payouts" => $this->getTotalTransactionAmountByType('payout', $month, $year),
            "total_income" => $this->getTotalTransactionAmountByType('income', $month, $year),
            "collectors_stats" => $collectorsStats
        ];
    }
    
    public function getTransactionsAmountByFilter($year)
    {
        $data = [];
        $currentMonth = date('n');
        $currentYear = date('Y');
        
        for ($month = 1; $month <= 12; $month++) {
            if ($year == $currentYear && $month > $currentMonth) {
                break;
            }
            
            $amount = $this->getTotalTransactionAmountByType('customer_deposit', $month, $year);
            $monthStr = Carbon::createFromDate(1900, $month, 1)->format('M');
            
            $data[] = [
                'month' => $monthStr,
                'amount' => $amount
            ];
        }
        
        return $data;
    }
}
