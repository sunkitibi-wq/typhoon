<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Models\Banking\KycVerification;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('kyc', [AdminController::class, 'kyc'])->name('kyc');
        Route::post('kyc/{kyc}/approve', [AdminController::class, 'approveKyc'])->name('kyc.approve');
        Route::post('kyc/{kyc}/reject', [AdminController::class, 'rejectKyc'])->name('kyc.reject');
        Route::get('accounts', [AdminController::class, 'accounts'])->name('accounts');
        Route::post('accounts/{account}/approve', [AdminController::class, 'approveAccount'])->name('accounts.approve');
        Route::post('accounts/{account}/reject', [AdminController::class, 'rejectAccount'])->name('accounts.reject');
        Route::post('accounts/{account}/freeze', [AdminController::class, 'freezeAccount'])->name('accounts.freeze');
        Route::post('accounts/{account}/unfreeze', [AdminController::class, 'unfreezeAccount'])->name('accounts.unfreeze');
        Route::post('accounts/{account}/close', [AdminController::class, 'closeAccount'])->name('accounts.close');
        // add more admin routes as needed
    });

