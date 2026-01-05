<?php

use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WithdrawalController;
use App\Models\Operator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

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
        '/user/transactions',
        [ReferralController::class, 'myTransactions']
    );
    Route::get(
        '/user/referral-link',
        [UserController::class, 'referralLink']
    );
    Route::post('/withdrawals', [WithdrawalController::class, 'store']);
    Route::post('/roulette/{roulette}/spin', [InvestmentController::class, 'spin']);
    Route::get('/roulettes', [ReferralController::class, 'myRoulettes']);

});

Route::get('/roulettes/{id}',[ReferralController::class, 'myRouletteById']);

Route::middleware('auth:sanctum')->get('/operators', function () {
    $user = Auth::user();

    return response()->json(
        Operator::where('country_code', $user->country_code)
            ->get(['id', 'name', 'code'])
    );
});

