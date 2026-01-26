<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Password Reset Routes (Web Interface)
Route::get('/auth/reset-password', function (Request $request) {
    $token = $request->query('token');
    if (!$token) {
        return response()->json(['message' => 'Token missing'], 400);
    }
    return view('reset_password', ['token' => $token]);
});

// Success View
Route::get('/reset-success', function() { 
    return "Password reset successful! You can now login via the app."; 
});
