<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeneficiaryController;
use App\Http\Controllers\Api\CryptoController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\SepaController;
use App\Http\Controllers\Api\SwiftController;
use App\Http\Controllers\Api\StandingOrderController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\OraclePosController;
use Illuminate\Support\Facades\Route;

// Public routes (with rate limiting)
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:3,60');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,60');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,15');

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
    Route::post('/kyc/documents', [KycController::class, 'uploadDocument'])->middleware('throttle:10,60');

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
    Route::post('/crypto/transactions/send', [CryptoController::class, 'sendTransaction']);
    Route::post('/crypto/buy-fiat', [CryptoController::class, 'buyWithFiat']);
    Route::post('/crypto/sell-fiat', [CryptoController::class, 'sellToFiat']);

    // Portfolio
    Route::get('/portfolio', [PortfolioController::class, 'overview']);

    // POS Terminal Integration
    Route::post('/pos/sale', [PosController::class, 'processTerminalPayment']);
    Route::post('/pos/refund', [PosController::class, 'processTerminalRefund']);
    Route::get('/pos/terminals', [PosController::class, 'indexTerminals']);
    Route::post('/pos/terminals', [PosController::class, 'pairTerminal']);
    Route::delete('/pos/terminals/{terminal}', [PosController::class, 'deleteTerminal']);
    Route::post('/pos/terminals/{terminal}/toggle', [PosController::class, 'toggleTerminal']);
    Route::get('/pos/transactions', [PosController::class, 'indexTransactions']);
    Route::post('/pos/transactions/{posTransaction}/refund', [PosController::class, 'refundTransaction']);

    // Oracle POS & Crypto smartPOS
    Route::post('/pos/oracle/configure-terminal', [OraclePosController::class, 'configureTerminal']);
    Route::post('/pos/oracle/charge', [OraclePosController::class, 'charge']);
    Route::get('/pos/oracle/charge/{reference}/status', [OraclePosController::class, 'status']);
    Route::post('/pos/oracle/simulate-payment', [OraclePosController::class, 'simulatePayment']);

    // SEPA
    Route::get('/sepa/transfers', [SepaController::class, 'index']);
    Route::post('/sepa/transfers', [SepaController::class, 'storeTransfer']);
    Route::post('/sepa/direct-debits', [SepaController::class, 'storeDirectDebit']);

    // SWIFT
    Route::get('/swift/transfers', [SwiftController::class, 'index']);
    Route::post('/swift/transfers', [SwiftController::class, 'store']);

    // Standing Orders
    Route::get('/standing-orders', [StandingOrderController::class, 'index']);
    Route::post('/standing-orders', [StandingOrderController::class, 'store']);
    Route::post('/standing-orders/{order}/toggle', [StandingOrderController::class, 'toggle']);
    Route::delete('/standing-orders/{order}', [StandingOrderController::class, 'destroy']);

    // Deposits
    Route::get('/deposits', [DepositController::class, 'index']);
    Route::post('/deposits', [DepositController::class, 'store']);

    // Loans
    Route::get('/loans', [LoanController::class, 'index']);
    Route::post('/loans', [LoanController::class, 'store']);
    Route::get('/loans/{loan}', [LoanController::class, 'show']);
    Route::post('/loans/{loan}/pay', [LoanController::class, 'pay']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Admin-specific endpoints
});
