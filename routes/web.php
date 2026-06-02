<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BankingController;
use App\Http\Controllers\CorporateController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::aliasMiddleware('role', App\Http\Middleware\EnsureUserHasRole::class);

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
    'rates' => fn() => \App\Models\Banking\ExchangeRate::where('quote_currency', 'EUR')
        ->latest('last_refreshed_at')
        ->get()
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
    $user = request()->user();
    if ($user?->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('banking.dashboard');
})->name('dashboard');

    Route::prefix('banking')->name('banking.')->group(function () {
        Route::get('dashboard', [BankingController::class, 'dashboard'])->name('dashboard');
        Route::get('accounts', [BankingController::class, 'accounts'])->name('accounts');
        Route::post('accounts', [BankingController::class, 'createAccount'])->name('accounts.store');
        Route::match(['get', 'post'], 'accounts/create', [BankingController::class, 'createAccount'])->name('accounts.create');
        Route::get('accounts/{account}', [BankingController::class, 'showAccount'])->name('accounts.show');
        Route::get('transactions', [BankingController::class, 'transactions'])->name('transactions');
        Route::match(['get', 'post'], 'transfer', [BankingController::class, 'transfer'])->name('transfer');
        Route::match(['get', 'post'], 'kyc', [BankingController::class, 'kyc'])->name('kyc');
        Route::get('kyc-status', [BankingController::class, 'kycStatus'])->name('kyc-status');
        Route::match(['get', 'post'], 'crypto', [BankingController::class, 'crypto'])->name('crypto');
        Route::match(['get', 'post'], 'beneficiaries', [BankingController::class, 'beneficiaries'])->name('beneficiaries');
        Route::delete('beneficiaries/{beneficiary}', [BankingController::class, 'destroyBeneficiary'])->name('beneficiaries.destroy');
        Route::post('accounts/{account}/freeze', [BankingController::class, 'freezeAccount'])->name('accounts.freeze');
        Route::post('accounts/{account}/unfreeze', [BankingController::class, 'unfreezeAccount'])->name('accounts.unfreeze');
        Route::post('accounts/{account}/close', [BankingController::class, 'closeAccount'])->name('accounts.close');
        Route::match(['get', 'post'], 'standing-orders', [BankingController::class, 'standingOrders'])->name('standing-orders');
        Route::post('standing-orders/{order}/toggle', [BankingController::class, 'toggleStandingOrder'])->name('standing-orders.toggle');
        Route::delete('standing-orders/{order}', [BankingController::class, 'destroyStandingOrder'])->name('standing-orders.destroy');
        Route::match(['get', 'post'], 'sepa', [BankingController::class, 'sepa'])->name('sepa');
        Route::match(['get', 'post'], 'swift', [BankingController::class, 'swift'])->name('swift');
        Route::get('statements', [BankingController::class, 'statements'])->name('statements');
        Route::get('statements/{account}/download', [BankingController::class, 'downloadStatement'])->name('statements.download');
        Route::match(['get', 'post'], 'notifications', [BankingController::class, 'notifications'])->name('notifications');
        Route::get('onboarding', [BankingController::class, 'onboarding'])->name('onboarding');
        Route::match(['get', 'post'], 'deposit', [BankingController::class, 'deposit'])->name('deposit');
        Route::match(['get', 'post'], 'loans', [BankingController::class, 'loans'])->name('loans');
        Route::get('loans/{loan}', [BankingController::class, 'showLoan'])->name('loans.show');
        Route::post('loans/{loan}/pay', [BankingController::class, 'loanMakePayment'])->name('loans.pay');
        Route::get('pos', [BankingController::class, 'posDashboard'])->name('pos');
        Route::post('pos/terminals', [BankingController::class, 'pairTerminal'])->name('pos.terminals.store');
        Route::post('pos/terminals/{terminal}/toggle', [BankingController::class, 'toggleTerminal'])->name('pos.terminals.toggle');
        Route::delete('pos/terminals/{terminal}', [BankingController::class, 'deleteTerminal'])->name('pos.terminals.destroy');
        Route::post('pos/transactions/{posTransaction}/refund', [BankingController::class, 'refundPosTransaction'])->name('pos.transactions.refund');
    });

    Route::prefix('webhooks')->name('webhooks.')->group(function () {
        Route::post('banking/{event}', [\App\Http\Controllers\WebhookController::class, 'handle'])->name('banking');
    });

    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('kyc', [AdminController::class, 'kyc'])->name('kyc');
        Route::post('kyc/{kyc}/approve', [AdminController::class, 'approveKyc'])->name('kyc.approve');
        Route::post('kyc/{kyc}/reject', [AdminController::class, 'rejectKyc'])->name('kyc.reject');
        Route::get('kyc/documents/{document}', [AdminController::class, 'viewKycDocument'])->name('kyc.document');
        Route::get('monitoring', [AdminController::class, 'monitoring'])->name('monitoring');
        Route::post('monitoring/{alert}/resolve', [AdminController::class, 'resolveAlert'])->name('monitoring.resolve');
        Route::get('users', [AdminController::class, 'users'])->name('users');
        Route::match(['get', 'post'], 'users/create', [AdminController::class, 'createUser'])->name('users.create');
        Route::match(['get', 'post'], 'users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::get('users/{user}', [AdminController::class, 'userDetail'])->name('users.detail');
        Route::get('accounts', [AdminController::class, 'accounts'])->name('accounts');
        Route::post('accounts/{account}/approve', [AdminController::class, 'approveAccount'])->name('accounts.approve');
        Route::post('accounts/{account}/reject', [AdminController::class, 'rejectAccount'])->name('accounts.reject');
        Route::post('accounts/{account}/freeze', [AdminController::class, 'freezeAccount'])->name('accounts.freeze');
        Route::post('accounts/{account}/unfreeze', [AdminController::class, 'unfreezeAccount'])->name('accounts.unfreeze');
        Route::post('accounts/{account}/close', [AdminController::class, 'closeAccount'])->name('accounts.close');
        Route::get('audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');
        Route::match(['get', 'post'], 'api-clients', [AdminController::class, 'apiClients'])->name('api-clients');
        Route::post('api-clients/{client}/revoke', [AdminController::class, 'revokeApiClient'])->name('api-clients.revoke');
        Route::get('crypto-deposits', [AdminController::class, 'cryptoDeposits'])->name('crypto-deposits');
        Route::post('crypto-deposits/{deposit}/confirm', [AdminController::class, 'confirmCryptoDeposit'])->name('crypto-deposits.confirm');
        Route::get('crypto-withdrawals', [AdminController::class, 'cryptoWithdrawals'])->name('crypto-withdrawals');
        Route::post('crypto-withdrawals/{withdrawal}/approve', [AdminController::class, 'approveCryptoWithdrawal'])->name('crypto-withdrawals.approve');
        Route::get('fee-schedules', [AdminController::class, 'feeSchedules'])->name('fee-schedules');
        Route::post('fee-schedules', [AdminController::class, 'createFeeSchedule'])->name('fee-schedules.create');
        Route::post('fee-schedules/{fee}/toggle', [AdminController::class, 'toggleFeeSchedule'])->name('fee-schedules.toggle');
        Route::get('platform-settings', [AdminController::class, 'platformSettings'])->name('platform-settings');
        Route::post('platform-settings', [AdminController::class, 'updatePlatformSettings'])->name('platform-settings.update');
        Route::get('loans', [AdminController::class, 'loans'])->name('loans');
        Route::get('loans/{loan}', [AdminController::class, 'showLoan'])->name('loans.show');
        Route::post('loans/{loan}/underwrite', [AdminController::class, 'underwriteLoan'])->name('loans.underwrite');
        Route::post('loans/{loan}/approve', [AdminController::class, 'approveLoan'])->name('loans.approve');
        Route::post('loans/{loan}/reject', [AdminController::class, 'rejectLoan'])->name('loans.reject');
        Route::post('loans/{loan}/disburse', [AdminController::class, 'disburseLoan'])->name('loans.disburse');
    });

    Route::middleware(['role:corporate'])->prefix('corporate')->name('corporate.')->group(function () {
        Route::get('dashboard', [CorporateController::class, 'dashboard'])->name('dashboard');
        Route::match(['get', 'post'], 'business-profile', [CorporateController::class, 'businessProfile'])->name('business-profile');
        Route::match(['get', 'post'], 'team', [CorporateController::class, 'team'])->name('team');
        Route::match(['get', 'post'], 'bulk-payments', [CorporateController::class, 'bulkPayments'])->name('bulk-payments');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/client.php';
