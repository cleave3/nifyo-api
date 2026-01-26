<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use OpenApi\Annotations as OA;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
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
    
    // Helpers to get UID from token since we can't fully rely on custom claims in request directly
    // in all middleware setups without custom parsing. But auth()->user() should work.
    private function getCurrentUid() {
        return auth('api')->user()->uid;
    }
    
    private function getCurrentBusinessId() {
         return auth('api')->user()->business_id;
    }

    /**
     * @OA\Post(
     *      path="/auth/login-user",
     *      operationId="loginUser",
     *      tags={"Auth"},
     *      summary="Login User",
     *      description="Login as a regular user (staff/root/user)",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="secret")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */
    public function loginUser(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|string',
                'password' => 'required|string',
            ]);

            $data['user_type'] = ['user', 'root', 'staff'];
            $result = $this->authService->login($data);

            return $this->sendResponse($result, "Login successful");
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function loginAdmin(Request $request)
    {
         try {
            $data = $request->validate([
                'email' => 'required|string',
                'password' => 'required|string',
            ]);

            $data['user_type'] = ['admin'];
            $result = $this->authService->login($data);

            return $this->sendResponse($result, "Login successful");
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }

    public function registerTeamMember(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'phone' => 'nullable|string',
                'permissions' => 'nullable|array',
            ]);
            
            $businessId = $this->getCurrentBusinessId();

            $member = $this->authService->createUser($data, $businessId);

            // Hide password_hash etc if returned directly
            // For now assuming $member is Safe User model
            return $this->sendResponse($member, "Member add successfully", true, 201);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
    
    public function getUserPermissions()
    {
        // Static list from python: user_permission_list
        // We'll just define it or return existing mocked permissions
        // For simplicity returning empty list or "all"
        $permissions = [
            'dashboard' => [
                [
                    'name' => 'view payouts',
                    'action' => 'dashboard.payouts',
                ],
                [
                    'name' => 'view collections',
                    'action' => 'dashboard.collections',
                ],
                [
                    'name' => 'view charts',
                    'action' => 'dashboard.chart',
                ],
                [
                    'name' => 'view customers',
                    'action' => 'dashboard.customers',
                ],
                [
                    'name' => 'view balances',
                    'action' => 'dashboard.customer_balances',
                ],
                [
                    'name' => 'view revenue',
                    'action' => 'dashboard.revenue',
                ],
                [
                    'name' => 'view staffs',
                    'action' => 'dashboard.staffs',
                ],
                [
                    'name' => 'view pending transactions',
                    'action' => 'dashboard.pending',
                ],
            ],
            'customers' => [
                [
                    'name' => 'update customers',
                    'action' => 'customer.update',
                ],
                [
                    'name' => 'create customers',
                    'action' => 'customer.create',
                ],
                [
                    'name' => 'view customers',
                    'action' => 'customer.view',
                ],
                [
                    'name' => 'delete customers',
                    'action' => 'customer.delete',
                ],
                [
                    'name' => 'view balances',
                    'action' => 'customer.view_balances',
                ],
            ],
            'business' => [
                [
                    'name' => 'view business information',
                    'action' => 'business.view',
                ],
                [
                    'name' => 'update business information',
                    'action' => 'business.update',
                ],
                [
                    'name' => 'update business preferences',
                    'action' => 'business.update_preferences',
                ],
            ],
            'transaction' => [
                [
                    'name' => 'view transactions',
                    'action' => 'transaction.view',
                ],
                [
                    'name' => 'initiate transactions',
                    'action' => 'transaction.initiate',
                ],
                [
                    'name' => 'approve transactions',
                    'action' => 'transaction.approve',
                ],
                [
                    'name' => 'modify transactions',
                    'action' => 'transaction.modify',
                ],
            ],
            'team' => [
                [
                    'name' => 'update user',
                    'action' => 'user.update',
                ],
                [
                    'name' => 'create user',
                    'action' => 'user.create',
                ],
                [
                    'name' => 'view user',
                    'action' => 'user.view',
                ],
                [
                    'name' => 'delete user',
                    'action' => 'user.delete',
                ],
            ],
            'settings' => [
                [
                    'name' => 'view settings',
                    'action' => 'settings.view',
                ],
                [
                    'name' => 'modify settings',
                    'action' => 'settings.modify',
                ],
            ],
            'metrics' => [
                [
                    'name' => 'staff performance',
                    'action' => 'metrics.staff_performance',
                ],
            ],
        ];
        return $this->sendResponse($permissions);
    }
    
    public function updateMemberStatus($userId, Request $request)
    {
        try {
            if ($userId == $this->getCurrentUid()) {
                 return $this->sendResponse(null, "Action Not Allowed", false, 403, "Action Not Allowed");
            }
            
            $data = $request->validate([
                'status' => 'required|string'
            ]);
            
            $this->authService->updateAuth($userId, $data);
             return $this->sendResponse(null, "status updated successfully");
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
    
    public function updateMemberPermissions($userId, Request $request)
    {
        try {
            $data = $request->validate([
                'permissions' => 'required|array'
            ]);
            
            $this->authService->updateUser($userId, $data);
            return $this->sendResponse(null, "permissions updated successfully");
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
    
    public function getUsersByBusiness(Request $request)
    {
        try {
            $users = $this->authService->getUsersByBusinessId($this->getCurrentBusinessId());
            return $this->sendResponse($users);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
    
    public function getUserById($userId)
    {
        try {
            $user = $this->authService->getProfileByUid($userId);
            $auth = $this->authService->getUserById($userId);
            
            if (!$auth) { // Python uses auth for Not Found check usually
                 throw new Exception("User not found", 404);
            }

            return $this->sendResponse(['user' => $user, 'auth' => $auth]);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
    
    public function verifyUserAccount(Request $request) 
    {
        try {
            $data = $request->validate([
                'email' => 'required|email',
                'code' => 'required|string'
            ]);
            
            $result = $this->authService->verifyUserAccount($data);
            return $this->sendResponse($result, "Email verified successfully");
            
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function resendVerificationCode(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email',
                'channel' => 'required|string' // 'email' or 'sms'
            ]);
            
            $this->authService->resendVerificationCode($data);
            return $this->sendResponse(null, "Please check your {$data['channel']} for your verification code");
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function socioAuth(Request $request)
    {
        // Not implemented in Service completely yet
        return $this->sendResponse(null, "Not Implemented", false, 501);
    }
    
    public function refreshToken(Request $request)
    {
        try {
            $result = $this->authService->refreshToken();
            return $this->sendResponse($result);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }
    
    public function getCurrentUser(Request $request)
    {
        try {
            
            $uid = $this->getCurrentUid();
            $user = $this->authService->getProfileByUid($uid);
            $auth = $this->authService->getUserById($uid);
            
            // Merge logic
            $data = array_merge(
                $user ? $user->toArray() : [],
                $auth ? collect($auth->toArray())->except('password_hash')->toArray() : []
            );
            
            return $this->sendResponse($data);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function changePassword(Request $request)
    {
        try {
             $data = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string'
            ]);
            
            $uid = $this->getCurrentUid();
            $result = $this->authService->changePassword($uid, $data['current_password'], $data['new_password']);
            return $this->sendResponse(null, $result);
            
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function forgotPassword(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email'
            ]);
            
            $result = $this->authService->forgotPassword($data['email']);
            return $this->sendResponse(null, $result);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function resetPasswordForm(Request $request, $token)
    {
        // Simply return basic view or JSON info. Python returns HTML.
        // We will return a view.
        return view('reset_password', ['token' => $token]);
    }
    
    public function resetAccountPassword(Request $request, $token)
    {
        try {
            $data = $request->validate([
                'new_password' => 'required|string',
                'confirm_password' => 'required|same:new_password'
            ]);
            
            $result = $this->authService->resetAccountPassword($token, $data['new_password']);
            return $this->sendResponse($result['message']);
        } catch (Exception $e) {
             return $this->handleException($e);
        }
    }
    
    public function logout(Request $request)
    {
        try {
            $this->authService->logout($this->getCurrentUid());
            return $this->sendResponse(null, "Logout successful");
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
