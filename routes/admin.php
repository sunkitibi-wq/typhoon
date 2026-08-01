<?php

use App\Models\Banking\KycVerification;
use App\Models\Banking\MonitoringAlert;
use App\Services\ReportService;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('home', function () {
            $reportService = app(ReportService::class);
            $data = $reportService->adminDashboard();

            $pendingKycs = KycVerification::with('user')
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get();

            $recentAlerts = MonitoringAlert::with('user', 'transaction')
                ->latest()
                ->take(5)
                ->get();

            return view('admin.dashboard', compact('data', 'pendingKycs', 'recentAlerts'));
        })->name('home');
    });
