<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Response;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    private function sendResponse($data = null, $message = "Success", $status = true, $code = 200, $error = null)
    {
        return response()->json([
            "status" => $status,
            "code" => $code,
            "message" => $message,
            "data" => $data,
            "error" => $error,
        ], (int) $code);
    }
    
    private function getCurrentBusinessId() {
         return auth('api')->user()->business_id;
    }
    
    private function getCurrentUid() {
         return auth('api')->user()->uid;
    }

    public function createCustomer(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string',
                'email' => 'nullable|email',
                'phone' => 'nullable|string',
                'image_url' => 'nullable|string',
                'address' => 'nullable|string',
                'payment_frequency' => 'nullable|string', // default monthly in DB
                'sms_alert' => 'nullable|string', // default YES
                'customer_type' => 'nullable|string',
                'customer_code' => 'nullable|string',
                'opening_balance' => 'nullable|numeric'
            ]);

            $businessId = $this->getCurrentBusinessId();
            $userUid = $this->getCurrentUid();

            $customer = $this->customerService->createCustomer($businessId, $userUid, $data);

            return $this->sendResponse($customer, "Customer created successfully", true, 201);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }

    public function listCustomers(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $search = $request->query('search');
            $customerType = $request->query('customer_type');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $status = $request->query('status');

            $businessId = $this->getCurrentBusinessId();
            
            // Date parsing if needed, but Eloquent might handle standard formats
            
            $result = $this->customerService->paginatedGetCustomers(
                $businessId, $page, $limit, $search, $customerType, $startDate, $endDate, $status
            );

            return $this->sendResponse($result);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function getAllCustomers(Request $request) 
    {
        try {
            $businessId = $this->getCurrentBusinessId();
            $result = $this->customerService->getCustomers($businessId);
            return $this->sendResponse($result);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function exportCustomersData(Request $request)
    {
        try {
            $businessId = $this->getCurrentBusinessId();
            list($tempFile, $fileName) = $this->customerService->exportCustomersData($businessId);
            
            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function getCustomerById($customerId)
    {
        try {
            $customer = $this->customerService->getCustomerProfile($customerId);
            if (!$customer) {
                return $this->sendResponse(null, "Customer not found", false, 404);
            }
            return $this->sendResponse($customer);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function updateCustomer($customerId, Request $request)
    {
        try {
            // Validation similar to create but allowing partial
             $data = $request->validate([
                'name' => 'nullable|string',
                'email' => 'nullable|email',
                'phone' => 'nullable|string',
                'image_url' => 'nullable|string',
                'address' => 'nullable|string',
                'payment_frequency' => 'nullable|string',
                'sms_alert' => 'nullable|string',
                'customer_type' => 'nullable|string',
                'customer_code' => 'nullable|string',
                'status' => 'nullable|string'
            ]);
            
            $updated = $this->customerService->updateCustomer($customerId, $data);
            return $this->sendResponse($updated, "Customer updated successfully");
            
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }

    private function handleException(Exception $e)
    {
        $code = (int) $e->getCode();
        if ($code < 100 || $code > 599) $code = 400;
        return $this->sendResponse(null, $e->getMessage(), false, $code, $e->getMessage());
    }
}
