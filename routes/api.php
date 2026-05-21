<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeneficiaryController;
use App\Http\Controllers\Api\CryptoController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::get('/account-types', [AccountController::class, 'getAccountTypes']);
Route::get('/crypto/currencies', [CryptoController::class, 'currencies']);
Route::get('/crypto/rates', [CryptoController::class, 'rates']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Accounts
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/accounts', [AccountController::class, 'create']);
    Route::get('/accounts/{account}', [AccountController::class, 'show']);
    Route::get('/accounts/{account}/statement', [AccountController::class, 'statement']);
    Route::post('/accounts/{account}/set-default', [AccountController::class, 'toggleDefault']);

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions/transfer', [TransactionController::class, 'transfer']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);

    // KYC
    Route::get('/kyc/status', [KycController::class, 'status']);
    Route::post('/kyc/submit', [KycController::class, 'submit']);
    Route::post('/kyc/documents', [KycController::class, 'uploadDocument']);

    // Beneficiaries
    Route::apiResource('beneficiaries', BeneficiaryController::class);

    // Crypto
    Route::get('/crypto/wallets', [CryptoController::class, 'wallets']);
    Route::post('/crypto/wallets', [CryptoController::class, 'createWallet']);
    Route::get('/crypto/orders', [CryptoController::class, 'orders']);
    Route::post('/crypto/orders', [CryptoController::class, 'placeOrder']);
    Route::get('/crypto/quote', [CryptoController::class, 'quote']);
    Route::get('/crypto/deposits', [CryptoController::class, 'deposits']);
    Route::post('/crypto/deposits', [CryptoController::class, 'recordDeposit']);
    Route::post('/crypto/deposits/{deposit}/confirm', [CryptoController::class, 'confirmDeposit']);
    Route::get('/crypto/withdrawals', [CryptoController::class, 'withdrawals']);
    Route::post('/crypto/withdrawals', [CryptoController::class, 'requestWithdrawal']);

    // Portfolio
    Route::get('/portfolio', [PortfolioController::class, 'overview']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Admin-specific endpoints
});
