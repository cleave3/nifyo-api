<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionApproval;
use App\Models\Wallet; // Required for PDF header
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    public function createTransaction($userUid, $businessId, array $data)
    {
        // Python: exclude="charges" from data, sets charges=0.0 unless specified
        // data usually contains: amount, description, transaction_type, meta_data

        // $data should come validated

        $transactionData = [
            'id' => (string) Str::uuid(),
            'business_id' => $businessId,
            'initiator_id' => $userUid,
            'amount' => $data['amount'],
            'transaction_type' => $data['transaction_type'] ?? 'customer_deposit',
            'status' => 'pending',
            'description' => $data['description'] ?? null,
            'meta_data' => $data['meta_data'] ?? [], // Eloquent cast array to JSON automatically
            'requires_approval' => true,
            'number_of_required_approval' => 1,
            'charges' => $data['charges'] ?? 0.0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $transaction = Transaction::create($transactionData);

        return $this->formatResponse($transaction);
    }

    public function getTransactionById($transactionId)
    {
        $transaction = Transaction::with(['initiator', 'updated_by'])->find($transactionId);
        if (! $transaction) {
            throw new Exception('Transaction not found', 404);
        }

        return $transaction;
    }

    public function paginatedGetTransactions($businessId, $page, $limit, $status = null, $startDate = null, $endDate = null, $userId = null)
    {
        $query = Transaction::with(['initiator', 'updated_by'])->orderBy('created_at', 'desc');

        if ($businessId) {
            $query->where('business_id', $businessId);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($userId) {
            $query->where('initiator_id', $userId);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        if (! $page || ! $limit) {
            $transactions = $query->get();

            return ['transactions' => $transactions->map(fn ($t) => $this->formatResponse($t))];
        }

        $total = $query->count();
        $transactions = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'transactions' => $transactions->map(fn ($t) => $this->formatResponse($t)),
        ];
    }

    public function approveTransaction($transactionId, $userUid)
    {
        return DB::transaction(function () use ($transactionId, $userUid) {
            $transaction = Transaction::lockForUpdate()->find($transactionId);

            if (! $transaction) {
                throw new Exception('Transaction not found', 404);
            }
            if ($transaction->status !== 'pending') {
                throw new Exception('Transaction is not pending', 400);
            }

            // Check previous approvals
            $existingApproval = TransactionApproval::where('transaction_id', $transactionId)
                ->where('user_id', $userUid)->exists();
            if ($existingApproval) {
                throw new Exception('You have already approved this transaction', 400);
            }

            // Check if approval limit reached (logic simplified to 1 for now as per Python default)
            $approvalCount = TransactionApproval::where('transaction_id', $transactionId)->count();
            if ($approvalCount >= $transaction->number_of_required_approval) {
                throw new Exception('Transaction Already approved', 400);
            }

            // Record Approval
            TransactionApproval::create([
                'id' => (string) Str::uuid(),
                'transaction_id' => $transactionId,
                'user_id' => $userUid,
            ]);

            // Finalize
            $transaction->status = 'completed';
            $transaction->updated_at = now();
            // $transaction->updated_by_id = $userUid; // Python doesn't explicitly set updated_by on approval unless implied
            $transaction->save();

            // Process Wallet Logic
            $this->processTransactionEffects($transaction, $userUid);

            return $this->formatResponse($transaction);
        });
    }

    public function declineTransaction($transactionId)
    {
        $transaction = Transaction::find($transactionId);
        if (! $transaction) {
            throw new Exception('Transaction not found', 404);
        }
        if ($transaction->status !== 'pending') {
            throw new Exception('Transaction is not pending', 400);
        }

        $transaction->status = 'cancelled';
        $transaction->save();

        return $this->formatResponse($transaction);
    }

    private function processTransactionEffects(Transaction $transaction, $userUid)
    {
        if (in_array($transaction->transaction_type, ['customer_deposit', 'payout'])) {
            $meta = $transaction->meta_data;
            if (! isset($meta['customer_id'])) {
                return;
            }

            $customerId = $meta['customer_id'];

            if (! Str::isUuid($customerId)) {
                // Log warning or throw exception? For now, just return/skip to avoid crash
                // Or maybe throw exception to inform user data is bad?
                // The user's error trace shows a crash, so maybe explicit exception is better or just ignoring it.
                // Given this is inside a transaction approval, failing loudly might be better than silent ignoring side effects.
                throw new Exception('Invalid Customer ID in transaction metadata', 400);
            }

            $customer = Customer::find($customerId);
            if (! $customer) {
                throw new Exception('Customer not found', 404);
            }

            // Update next payment date logic (simplified)
            if (isset($meta['next_payment_date'])) {
                $customer->next_payment_date = Carbon::parse($meta['next_payment_date']);
            } else {
                // Calculate next payment date based on frequency (Todo: Helper)
                // Keeping it valid for now without change if not provided
            }
            $customer->status = 'active';
            $customer->save();

            $credit = ($transaction->transaction_type === 'customer_deposit') ? $transaction->amount : 0;
            $debit = ($transaction->transaction_type === 'payout') ? $transaction->amount : 0;

            $this->insertIntoWallet($customerId, $credit, $debit, $transaction->description);

            // Handle Charges for Payout
            if ($transaction->transaction_type === 'payout' && $transaction->charges > 0) {
                $this->insertIntoWallet($customerId, 0, $transaction->charges, 'CHARGES|'.$transaction->description);

                // Create system transaction for charges
                Transaction::create([
                    'id' => (string) Str::uuid(),
                    'amount' => $transaction->charges,
                    'description' => "CHARGES of {$transaction->charges} for {$transaction->amount} payout",
                    'status' => 'completed',
                    'business_id' => $transaction->business_id,
                    'requires_approval' => false,
                    'charges' => 0.0,
                    'transaction_type' => 'income',
                    'number_of_required_approval' => 0,
                    'initiator_id' => $userUid,
                    'updated_by_id' => $userUid,
                ]);
            }

            // Send SMS (TODO)
        }
    }

    public function insertIntoWallet($customerId, $credit, $debit, $description)
    {
        return Wallet::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customerId,
            'credit' => $credit,
            'debit' => $debit,
            'description' => $description,
        ]);
    }

    public function getWalletBalance($customerId)
    {
        $credit = Wallet::where('customer_id', $customerId)->sum('credit');
        $debit = Wallet::where('customer_id', $customerId)->sum('debit');

        return $credit - $debit;
    }

    public function getWalletByCustomer($customerId, $page = 1, $limit = 10, $startDate = null, $endDate = null)
    {
        $query = Wallet::where('customer_id', $customerId)->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();
        $history = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'wallet_history' => $history,
        ];
    }

    public function getEntries($userUid, $year, $month)
    {
        $query = Transaction::with(['initiator', 'updated_by'])
            ->where('initiator_id', $userUid)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'desc') // Added ordering for UX
            ->get();

        return $query->map(fn ($t) => $this->formatResponse($t));
    }

    public function generateWalletStatementPdf($businessId, $customerId, $startDate = null, $endDate = null)
    {
        // config(['dompdf.public_path' => '/home/nifynmse/api.nifyofinance.com']); // in production
        $query = Wallet::where('customer_id', $customerId);
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $wallets = $query->get();
        $business = Business::find($businessId);
        $customer = Customer::find($customerId);

        $data = [
            'business' => $business,
            'customer' => $customer,
            'wallets' => $wallets,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $pdf = Pdf::loadView('pdf.wallet_statement', $data);

        return $pdf;
    }

    private function formatResponse(Transaction $transaction)
    {
        $data = $transaction->toArray();

        // Format Initiator
        $data['initiator'] = $transaction->initiator ? [
            'name' => trim($transaction->initiator->first_name.' '.$transaction->initiator->last_name),
        ] : null;

        // Format Updated By
        $data['updated_by'] = $transaction->updated_by ? [
            'name' => trim($transaction->updated_by->first_name.' '.$transaction->updated_by->last_name),
        ] : ['name' => ' '];

        // Format Approvers
        $approvals = TransactionApproval::with(['user'])->where('transaction_id', $transaction->id)->get();
        $data['approvers'] = $approvals->map(function ($approval) {
            $approvalData = $approval->toArray();
            $approvalData['approver'] = $approval->user ? [
                'name' => trim($approval->user->first_name.' '.$approval->user->last_name),
            ] : ['name' => 'Unknown'];

            // Remove full user object to keep it clean as per request example structure implies (or keep it if not specified to remove, but usually 'approver' replaces 'user')
            unset($approvalData['user']);

            return $approvalData;
        });

        return $data;
    }
}
