<?php

use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WithdrawalController;
use App\Models\Operator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('countries', [WithdrawalController::class, 'index']);
Route::middleware('auth:sanctum')->group(function () {
    // Profil utilisateur
    Route::get('me', [AuthController::class, 'me']);
    Route::post('me', [AuthController::class, 'updateProfile']);
    Route::post('profile/photo', [AuthController::class, 'updateProfilePhoto']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::get('user/dashboard', [UserController::class, 'dashboardServer']);
    Route::get('user/referrals', [ReferralController::class, 'myReferrals']);
    Route::post('investments', [InvestmentController::class, 'store']);
    Route::get(
        'withdraws',
        [ReferralController::class, 'myTransactions']
    );
    Route::get(
        '/user/referral-link',
        [UserController::class, 'referralLink']
    );
    Route::post('/withdraws', [WithdrawalController::class, 'store']);
    Route::post('/roulette/{roulette}/spin', [InvestmentController::class, 'spin']);
    Route::get('/roulettes', [ReferralController::class, 'myRoulettes']);
    Route::get('/commissions', [ReferralController::class, 'myCommissions']);
    Route::get('/operators', [WithdrawalController::class, 'operators']);
    Route::get('/user/balance', [UserController::class, 'balance']);
    Route::get('/withdraw-accounts', [UserController::class, 'index']);
    Route::post('/withdraw-accounts', [UserController::class, 'store']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
});
Route::get('/momo/status/{referenceId}', [UserController::class, 'checkStatus']);
Route::get('/roulettes/{id}',[ReferralController::class, 'myRouletteById']);


