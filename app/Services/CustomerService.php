<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\Wallet; // Not used in Python logic directly but might be needed
use App\Services\TransactionService;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerService
{
    // protected $transactionService; // Circular dependency potential if injected via constructor heavily
    
    // We will resolve it manually or use a facade if Laravel app grows.
    
    public function createCustomer(string $businessId, string $userUid, array $data)
    {
        return DB::transaction(function () use ($businessId, $userUid, $data) {
            $openingBalance = $data['opening_balance'] ?? 0.0;
            
            // Remove opening_balance from data for Customer model
            $customerData = $data;
            unset($customerData['opening_balance']);
            
            $customerData['id'] = (string) Str::uuid();
            $customerData['business_id'] = $businessId;
            $customerData['creator_id'] = $userUid;
            
            $customer = Customer::create($customerData);

            if ($openingBalance > 0) {
                 // Call TransactionService to create transaction
                 // For now, simpler to just inject or instantiate
                 $txService = new TransactionService();
                 
                 $txData = [
                    'transaction_type' => 'customer_deposit',
                    'amount' => $openingBalance,
                    'description' => "Opening balance deposit for " . ($customer->name ?? ''),
                    'meta_data' => [
                        'customer_id' => $customer->id,
                        'comment' => 'opening_deposit',
                        'customer' => $customer->name ?? ''
                    ]
                 ];
                 
                 $txService->createTransaction($userUid, $businessId, $txData);
            }

            return $customer;
        });
    }

    public function getCustomerProfile($customerId)
    {
        $customer = Customer::with('creator')->find($customerId);
        if (!$customer) {
            return null;
        }

        $txService = new TransactionService();
        $balance = $txService->getWalletBalance($customerId);

        $data = $customer->toArray();
        $data['balance'] = $balance;
        
        // Format creator name as in Python
        if ($customer->creator) {
            $data['creator'] = [
                'name' => trim(($customer->creator->first_name ?? '') . ' ' . ($customer->creator->last_name ?? ''))
            ];
        }

        return $data;
    }

    public function getCustomerById($customerId)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
             throw new Exception("Customer with ID $customerId not found.", 404);
        }
        return $customer;
    }

    public function updateCustomer($customerId, array $data)
    {
        $customer = $this->getCustomerById($customerId);
        $customer->update($data);
        return $customer;
    }
    
    public function paginatedGetCustomers($businessId, $page = 1, $limit = 10, $search = null, $customerType = null, $startDate = null, $endDate = null, $status = null)
    {
        $query = Customer::with('creator')->orderBy('created_at', 'desc');

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%$search%")
                  ->orWhere('email', 'ILIKE', "%$search%")
                  ->orWhere('customer_code', 'ILIKE', "%$search%")
                  ->orWhere('customer_type', 'ILIKE', "%$search%")
                  ->orWhere('phone', 'ILIKE', "%$search%");
            });
        }
        
        if ($customerType) {
            $query->where('customer_type', $customerType);
        }
        
        if ($status) {
             $query->where('status', $status);
        }
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();
        $customers = $query->skip(($page - 1) * $limit)->take($limit)->get();
        
        $txService = new TransactionService();
        
        $enhancedCustomers = $customers->map(function ($customer) use ($txService) {
             $data = $customer->toArray();
             $data['balance'] = $txService->getWalletBalance($customer->id);
             
             if ($customer->creator) {
                $data['creator'] = [
                    'name' => trim(($customer->creator->first_name ?? '') . ' ' . ($customer->creator->last_name ?? ''))
                ];
             }
             return $data;
        });

        return [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'customers' => $enhancedCustomers
        ];
    }
    
    public function getCustomers($businessId)
    {
        $query = Customer::orderBy('created_at', 'desc');
        if ($businessId) {
            $query->where('business_id', $businessId);
        }
        return $query->get();
    }
    
    public function exportCustomersData($businessId)
    {
        $query = Customer::orderBy('created_at', 'desc');
        if ($businessId) {
            $query->where('business_id', $businessId);
        }
        $customers = $query->get();
        
        $txService = new TransactionService();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customers');
        
        // Header
        $headers = ["ID", "Name", "Email", "Phone", "Customer Code", "Customer Type", "Balance", "Created At", "Updated At"];
        $sheet->fromArray($headers, NULL, 'A1');
        
        $rows = [];
        foreach ($customers as $customer) {
            $rows[] = [
                $customer->id,
                $customer->name ?? "",
                $customer->email ?? "",
                $customer->phone ?? "",
                $customer->customer_code ?? "",
                $customer->customer_type ?? "",
                $txService->getWalletBalance($customer->id) ?? 0.0,
                $customer->created_at ? $customer->created_at->format('Y-m-d H:i:s') : "",
                $customer->updated_at ? $customer->updated_at->format('Y-m-d H:i:s') : "",
            ];
        }
        
        if (!empty($rows)) {
            $sheet->fromArray($rows, NULL, 'A2');
        }
        
        $writer = new Xlsx($spreadsheet);
        $fileName = 'customers_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);
        
        return [$tempFile, $fileName];
    }
}
