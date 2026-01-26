<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\AnalyticsController;

Route::prefix('v1/auth')->group(function () {
    Route::post('/login-user', [AuthController::class, 'loginUser']);
    Route::post('/login-admin', [AuthController::class, 'loginAdmin']);
    Route::post('/socio-auth', [AuthController::class, 'socioAuth']);
    
    // Password Reset
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::get('/password-reset-confirm/{token}', [AuthController::class, 'resetPasswordForm']);
    Route::post('/password-reset-confirm/{token}', [AuthController::class, 'resetAccountPassword']);
    Route::get('/reset-success', function() { return view('success'); });
    
    // Protected Routes
    Route::middleware('auth:api')->group(function () {
        Route::post('/register-member', [AuthController::class, 'registerTeamMember']);
        Route::get('/user-permissions', [AuthController::class, 'getUserPermissions']);
        
        Route::patch('/update-status/{user_id}', [AuthController::class, 'updateMemberStatus']);
        Route::patch('/update-permissions/{user_id}', [AuthController::class, 'updateMemberPermissions']);
        
        Route::get('/users', [AuthController::class, 'getUsersByBusiness']);
        Route::get('/user/{user_id}', [AuthController::class, 'getUserById']);
        
        Route::post('/verify-email', [AuthController::class, 'verifyUserAccount']);
        Route::post('/resend-verification-code', [AuthController::class, 'resendVerificationCode']);
        
        Route::get('/refresh-token', [AuthController::class, 'refreshToken']);
        Route::get('/me', [AuthController::class, 'getCurrentUser']);
        Route::patch('/change-password', [AuthController::class, 'changePassword']);
        Route::get('/logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('v1/business')->middleware('auth:api')->group(function () {
    Route::post('', [BusinessController::class, 'submitBusiness']);
    Route::get('/me', [BusinessController::class, 'getMyBusinessInfo']);
    Route::patch('/preferences', [BusinessController::class, 'updatePreferences']);
});

Route::prefix('v1/customer')->middleware('auth:api')->group(function () {
    Route::post('', [CustomerController::class, 'createCustomer']);
    Route::get('', [CustomerController::class, 'listCustomers']);
    Route::get('/all', [CustomerController::class, 'getAllCustomers']);
    Route::get('/export', [CustomerController::class, 'exportCustomersData']);
    Route::get('/{customer_id}', [CustomerController::class, 'getCustomerById']);
    Route::patch('/{customer_id}', [CustomerController::class, 'updateCustomer']);
});

Route::prefix('v1/transaction')->middleware('auth:api')->group(function () {
    Route::post('', [TransactionController::class, 'createTransaction']);
    Route::get('', [TransactionController::class, 'listTransactions']);
    Route::get('/download-statement/{customer_id}', [TransactionController::class, 'downloadStatement']);
    Route::get('/my-entries', [TransactionController::class, 'getMyEntries']);
    
    Route::get('/{transaction_id}', [TransactionController::class, 'getTransactionById']);
    
    Route::patch('/{transaction_id}/approve', [TransactionController::class, 'approveTransaction']);
    Route::patch('/{transaction_id}/decline', [TransactionController::class, 'declineTransaction']);
    
    Route::get('/wallet-history/{customer_id}', [TransactionController::class, 'getWalletBalance']);
});

Route::prefix('v1/analytics')->middleware('auth:api')->group(function () {
    Route::get('/overview-stats', [AnalyticsController::class, 'getOverviewStats']);
    Route::get('/transactions-initiator-stats', [AnalyticsController::class, 'getTransactionsCountByInitiator']);
    Route::get('/transaction-year-stats', [AnalyticsController::class, 'getTransactionsAmountByFilter']);
    Route::get('/due-collections', [AnalyticsController::class, 'getCustomersWithDueCollections']);
});
