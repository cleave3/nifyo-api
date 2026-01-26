<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Exception;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
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

    public function getOverviewStats(Request $request)
    {
        try {
            $stats = $this->analyticsService->getOverviewStats();
            return $this->sendResponse($stats);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }

    public function getTransactionsCountByInitiator(Request $request)
    {
        try {
            $month = $request->query('month', date('n'));
            $year = $request->query('year', date('Y'));
            
            $stats = $this->analyticsService->getTransactionsCountByInitiator($month, $year);
            return $this->sendResponse($stats);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function getTransactionsAmountByFilter(Request $request)
    {
        try {
            $year = $request->query('year', 2025);
            $stats = $this->analyticsService->getTransactionsAmountByFilter($year);
            return $this->sendResponse($stats);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function getCustomersWithDueCollections(Request $request)
    {
        try {
            $customers = $this->analyticsService->getCustomersWithDueCollections();
            return $this->sendResponse($customers);
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
