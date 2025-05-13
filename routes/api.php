<?php

use App\Http\Controllers\Admin\MyProfileController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\PostController;
use App\Http\Controllers\User\ProfileController;
use Illuminate\Support\Facades\Route;

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
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

    // admin
    Route::middleware('admin')->group(function () {
        Route::post('/update-admin-profile', [MyProfileController::class, 'updateAdminProfile']);
    });


    // user
    Route::middleware('user')->group(function () {
        Route::post('/update-user-profile', [ProfileController::class, 'updateUserProfile']);
        Route::post('/create-post', [PostController::class, 'createPost']);
        Route::get('/get-post', [PostController::class, 'getPost']);
    });

});
