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
        // add more admin routes as needed
    });

