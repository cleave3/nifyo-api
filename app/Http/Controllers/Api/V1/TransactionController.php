<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Exception;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
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
    
    private function getCurrentUid() {
         return auth('api')->user()->uid;
    }
    
    private function getCurrentBusinessId() {
         return auth('api')->user()->business_id;
    }

    public function createTransaction(Request $request)
    {
        try {
            $data = $request->validate([
                'amount' => 'required|numeric',
                'description' => 'required|string',
                'transaction_type' => 'required|string',
                'meta_data' => 'nullable|array',
                'charges' => 'nullable|numeric'
            ]);

            $businessId = $this->getCurrentBusinessId();
            $userUid = $this->getCurrentUid();

            $transaction = $this->transactionService->createTransaction($userUid, $businessId, $data);

            return $this->sendResponse($transaction, "Transaction created successfully", true, 201);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }

    public function listTransactions(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $status = $request->query('status');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $userId = $request->query('user_id');

            $businessId = $this->getCurrentBusinessId();
            
            $result = $this->transactionService->paginatedGetTransactions(
                $businessId, $page, $limit, $status, $startDate, $endDate, $userId
            );

            return $this->sendResponse($result);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function getTransactionById($transactionId)
    {
        try {
            $transaction = $this->transactionService->getTransactionById($transactionId);
            return $this->sendResponse($transaction);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
    
    public function approveTransaction($transactionId)
    {
        try {
            $userUid = $this->getCurrentUid();
            $result = $this->transactionService->approveTransaction($transactionId, $userUid);
            return $this->sendResponse($result, "Transaction approved successfully");
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function declineTransaction($transactionId)
    {
        try {
            $result = $this->transactionService->declineTransaction($transactionId);
             return $this->sendResponse($result, "Transaction declined successfully");
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function getWalletBalance($customerId, Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            
            $result = $this->transactionService->getWalletByCustomer($customerId, $page, $limit, $startDate, $endDate);
            return $this->sendResponse($result);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function downloadStatement($customerId, Request $request)
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $businessId = $this->getCurrentBusinessId();
            
            $pdf = $this->transactionService->generateWalletStatementPdf($businessId, $customerId, $startDate, $endDate);
            
            return $pdf->download('wallet_statement.pdf');
        } catch (Exception $e) {
             return $this->handleException($e);
             // Note: download return binary, json error might break client expectation if not handled
        }
    }
    
    public function getMyEntries(Request $request)
    {
        try {
            $month = $request->query('month', date('n'));
            $year = $request->query('year', date('Y'));
            $userUid = $this->getCurrentUid();
            
            $result = $this->transactionService->getEntries($userUid, $year, $month);
            
            return $this->sendResponse($result);
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
