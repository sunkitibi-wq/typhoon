<?php

use Illuminate\Support\Facades\Route;

// Client routes – loaded via routes/web.php
// These routes use a simple closure to render the client home view.
// Extend as needed for additional client functionality.

Route::middleware(['auth'])
    ->prefix('client')
    ->name('client.')
    ->group(function () {
        Route::get('home', function () {
            $user = auth()->user();
            $accounts = $user->accounts()->with('accountType')->get();
            $transactions = \App\Models\Banking\Transaction::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            return view('client.home', compact('accounts', 'transactions'));
        })->name('home');
        // Add more client routes here.
    });
