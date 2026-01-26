<?php

namespace App\Services;

use App\Models\Auth;
use App\Models\User;
use App\Models\Token;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;
use App\Mail\ForgotPasswordEmail;

class AuthService
{
    public function createUser(array $data, ?string $businessId)
    {
        if (!empty($data['email']) && Auth::where('email', $data['email'])->exists()) {
             throw new Exception('User with this email already exists', 400); 
        }
        if (!empty($data['phone']) && Auth::where('phone', $data['phone'])->exists()) {
             throw new Exception('User with this phone already exists', 400);
        }

        return DB::transaction(function () use ($data, $businessId) {
            $uid = (string) Str::uuid();
            $authData = [
                'uid' => $uid,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'business_id' => $businessId,
                'name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''),
                'user_type' => 'staff',
                'status' => 'active',
                'is_email_verified' => true,
                'is_phone_verified' => true,
                'has_password' => true,
                'two_factor_option' => 'email', // Default to email as per common logic
                'password_hash' => Hash::make($data['password']),
            ];
            
            Auth::create($authData);

            $userData = [
                'uid' => $uid,
                'business_id' => $businessId,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'permissions' => $data['permissions'] ?? [],
            ];

            $user = User::create($userData);
            
            // Send Notification (Email/SMS)
            if (!empty($data['email'])) {
                 $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
                 $link = "{$frontendUrl}/login";
                 try {
                     Mail::to($data['email'])->send(new WelcomeEmail(
                         $data['first_name'] ?? 'User', 
                         $data['email'], 
                         $data['phone'] ?? '', 
                         $data['password'], 
                         $link
                     ));
                 } catch (Exception $e) {
                      // Log error
                 }
            }
            
            return $user;
        });
    }
    
    public function getUsersByBusinessId($businessId)
    {
        $auths = Auth::where('business_id', $businessId)->orderBy('name')->get();
        
        $usersList = [];
        foreach ($auths as $auth) {
            $profile = User::find($auth->uid);
            if ($profile) {
                // Merge auth and profile data logic as in python
                $usersList[] = array_merge(
                    $auth->toArray(),
                    $profile->toArray()
                );
            }
        }
        return $usersList;
    }

    public function getUserById($uid)
    {
        return Auth::where('uid', $uid)->first();
    }
    
    public function getProfileByUid($uid)
    {
        return User::where('uid', $uid)->first();
    }
    
    public function updateAuth($uid, array $data)
    {
        $auth = Auth::where('uid', $uid)->first();
        if (!$auth) {
            throw new Exception('User not found', 404);
        }
        $auth->update($data);
        return $auth;
    }
    
    public function updateUser($uid, array $data)
    {
        $user = User::where('uid', $uid)->first();
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        $user->update($data);
        return $user;
    }

    public function login(array $credentials)
    {
        $login = $credentials['email'];
        $password = $credentials['password'];
        $userTypes = $credentials['user_type'] ?? ['user', 'root', 'staff'];

        $auth = Auth::where(function($q) use ($login) {
            $q->where('email', $login)->orWhere('phone', $login);
        })->whereIn('user_type', $userTypes)->first();

        if (!$auth || !Hash::check($password, $auth->password_hash)) {
            // Simplified check, real app might check has_password etc
            if ($auth && !$auth->has_password) {
                 throw new Exception('Invalid credentials', 401);
            }
            throw new Exception('Invalid credentials', 401);
        }

        if ($auth->status !== 'active') {
             throw new Exception('Account restricted', 403);
        }
        
        if (!$auth->is_email_verified) {
             throw new Exception('Account not verified', 403);
        }

        $token = auth('api')->login($auth);
        
        $auth->update(['last_login' => now()]);

        return [
            'access_token' => $token,
            'refresh_token' => $token, // Ideally different expiration
            'user' => ['uid' => $auth->uid]
        ];
    }
    
    public function verifyUserAccount(array $data)
    {
        $user = Auth::where('email', $data['email'])->first();
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        
        if ($user->is_email_verified) {
            throw new Exception('User already verified', 400);
        }
        
        // Verify Token logic
        $valid = $this->isVerificationTokenValid($data['email'], $data['code']);
        if (!$valid) {
            throw new Exception('Invalid Token', 400);
        }
        
        $user->update(['is_email_verified' => true]);
        
        $token = auth('api')->login($user);
        
        return [
            'access_token' => $token,
            'refresh_token' => $token
        ];
    }
    
    public function resendVerificationCode(array $data)
    {
        $email = $data['email'] ?? null;
        $user = Auth::where('email', $email)->first();
        
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        
        $code = (string) rand(100000, 999999);
        // Identify channel, email or phone (using existing phone)
        $identifier = $email; // Simplify to email for now as per logic if channel is email
        
        // In python: identifier = email if data.channel == ChannelEnum.email.value else telephone
        // We will assume email for now or implementation detail
        
        $this->upsertVerificationToken($identifier, $code);
        
        // Send Email
        try {
            Mail::to($email)->send(new \App\Mail\VerificationEmail($email, $code));
        } catch (Exception $e) {
             // Log error but proceed? Or fail? Python often logs and continues for bg tasks, but here it's sync.
        }
        
        return true;
    }
    
    private function upsertVerificationToken($identifier, $code)
    {
        Token::updateOrCreate(
            ['identifier' => $identifier],
            [
                'token' => $code,
                'is_active' => true,
                'expiry' => Carbon::now()->addMinutes(30)
            ]
        );
    }
    
    private function isVerificationTokenValid($identifier, $code)
    {
        $token = Token::where('identifier', $identifier)->first();
        if ($token && $token->token === $code && $token->is_active && Carbon::parse($token->expiry)->isFuture()) {
            $token->update(['is_active' => false]);
            return true;
        }
        return false;
    }
    
    public function changePassword($uid, $currentPassword, $newPassword)
    {
        $user = Auth::where('uid', $uid)->first();
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        
        if (!$user->has_password) {
            throw new Exception('Invalid Password', 400);
        }
        
        if (Hash::check($newPassword, $user->password_hash)) {
            throw new Exception('Your new password can not be the same as your current password', 400);
        }
        
        if (!Hash::check($currentPassword, $user->password_hash)) {
             throw new Exception('Invalid Password', 400);
        }
        
        $user->update(['password_hash' => Hash::make($newPassword)]);
        
        return "Password changed successfully";
    }
    
    public function forgotPassword($email)
    {
        $user = Auth::where('email', $email)->first();
        if (!$user) {
             throw new Exception('User not found', 404);
        }
        
        // Simulating temp token for reset link
        $tempToken = auth('api')->setTTL(60)->claims(['isTemp' => true, 'email' => $email])->login($user);
        

        // Send Email with link
        $baseUrl = env('BASE_URL', 'http://localhost:8000');
        $link = "{$baseUrl}/api/v1/auth/password-reset-confirm/{$tempToken}";
        
        try {
            Mail::to($email)->send(new ForgotPasswordEmail($user->name, $link));
        } catch (Exception $e) {
            // Log error but don't fail the request potentially? 
            // Python prints error in finally block but continues.
            // dd($e); 
            // echo $e->getMessage();
            throw new Exception($e->getMessage(), 400);
        }

        return "Please check your email for instructions to reset your password";
    }
    
    public function resetAccountPassword($token, $newPassword)
    {
        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            $claims = $payload->toArray();
            
            if (empty($claims['isTemp']) || empty($claims['email'])) {
                 throw new Exception('Invalid Token', 400);
            }
            // Check blacklist? JWTAuth handles blacklist of the token itself if invalidated.
            
            $user = Auth::where('email', $claims['email'])->first();
            if (!$user) {
                throw new Exception('User not found', 404);
            }
            
            if (Hash::check($newPassword, $user->password_hash)) {
                 throw new Exception('You cannot use your old password', 400);
            }
            
            $user->update(['password_hash' => Hash::make($newPassword)]);
            
            JWTAuth::invalidate($token);
            
            return ['message' => 'Password reset Successfully'];
            
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
             throw new Exception('Invalid or Expired Link', 400);
        } catch (Exception $e) {
             throw new Exception($e->getMessage(), 400);
        }
    }

    public function socioAuthentication($data)
    {
        // TODO: Implement Google Verify Token logic via library or HTTP call
        // For now mocking a successful verification if token is present
        
        // This is a placeholder as actual Google Verify requires "google/apiclient"
        // Returning dummy successful data
        
        $email = "mock_socio@example.com"; // In real impl, get from Google
        // ... find or create user logic similar to Python ...
        
        throw new Exception("Socio Auth implementation requires Google Client Library", 501);
    }
    
    public function refreshToken()
    {
         // JWTAuth refresh handles the logic of invalidating old and creating new
         $newToken = auth('api')->refresh();
         return [
             'access_token' => $newToken,
             'refresh_token' => $newToken 
         ];
    }
    
    public function logout($uid)
    {
        auth('api')->logout(); // Invalidates current token
        
        $user = Auth::where('uid', $uid)->first();
        if ($user) {
             $user->update(['last_login' => now()]);
        }
    }
}
