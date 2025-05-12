<?php

use App\Http\Controllers\Admin\UserManageController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// public route for user
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// private route for user
Route::middleware('auth:api')->group(function () {
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    Route::post('/avatar', [AuthController::class, 'avatar']);
    Route::post('/update-avatar', [AuthController::class, 'updateAvatar']);

    // admin
    Route::middleware('admin')->group(function () {
        Route::post('/admin', [UserManageController::class, 'admin']);
    });


    // user
    Route::middleware('user')->group(function () {
        Route::post('/user', [UserManageController::class, 'user']);
    });


});
