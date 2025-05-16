<?php

use App\Http\Controllers\Admin\MyProfileController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\BookmarkController;
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
        // profile
        Route::post('/update-admin-profile', [MyProfileController::class, 'updateAdminProfile']);
    });


    // user
    Route::middleware('user')->group(function () {

        // bookmark
        Route::post('/toggle-bookmark', [BookmarkController::class, 'toggleBookmark']);
        Route::get('/get-bookmarks', [BookmarkController::class, 'getBookmarks']);
        Route::get('/view-post', [BookmarkController::class, 'viewPost']);

        // home
        Route::get('/discovery', [PostController::class, 'discovery']);
        Route::post('/discovery-toggle-follow', [PostController::class, 'discoveryToggleFollow']);
        Route::get('/following', [PostController::class, 'following']);

        // post
        Route::post('/create-post', [PostController::class, 'createPost']);
        Route::get('/search-follower', [PostController::class, 'searchFollower']);

        // profile
        Route::post('/update-user-profile', [ProfileController::class, 'updateUserProfile']);
        Route::get('/get-following', [ProfileController::class, 'getFollowing']);
        Route::get('/get-follower', [ProfileController::class, 'getFollower']);
        Route::post('/recent-post', [ProfileController::class, 'recentPost']);
        Route::get('/get-recent-post', [ProfileController::class, 'getRecentPost']);
        Route::get('/get-post', [ProfileController::class, 'getPost']);
    });
});
