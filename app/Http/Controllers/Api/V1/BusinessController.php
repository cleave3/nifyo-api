<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BusinessService;
use Illuminate\Http\Request;
use Exception;

class BusinessController extends Controller
{
    protected $businessService;

    public function __construct(BusinessService $businessService)
    {
        $this->businessService = $businessService;
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

    public function submitBusiness(Request $request)
    {
        try {
            // Validation based on BusinessCreate schema
            $data = $request->validate([
                'business_name' => 'required|string',
                'business_address' => 'nullable|string',
                'business_phone' => 'nullable|string',
                'business_email' => 'nullable|email',
                'logo_url' => 'nullable|string',
                'business_type' => 'nullable|string',
                'business_nature' => 'nullable|string',
                'business_website' => 'nullable|string',
                'business_reg_no' => 'nullable|string',
                'certificate_url' => 'nullable|string',
                'modules' => 'nullable|array',
            ]);

            $businessId = $this->getCurrentBusinessId();
            $result = $this->businessService->submitBusiness($businessId, $data);

            return $this->sendResponse($result);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getMyBusinessInfo(Request $request)
    {
        try {
            $businessId = $this->getCurrentBusinessId();
            if (!$businessId) {
                // If user has no business, return empty or error?
                // Python returns business.__dict__, assuming it finds one.
                return $this->sendResponse(null, "No business associated", false, 404);
            }
            
            $result = $this->businessService->getBusinessById($businessId);
            return $this->sendResponse($result);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    public function updatePreferences(Request $request)
    {
        try {
            $data = $request->validate([
                'sms_notification' => 'boolean',
                'email_notification' => 'boolean',
                'require_two_factor' => 'boolean',
                'sms_id' => 'nullable|string'
            ]);

            $businessId = $this->getCurrentBusinessId();
            $result = $this->businessService->updatePreferences($businessId, $data);

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
