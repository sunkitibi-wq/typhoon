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
            return view('client.home');
        })->name('home');
        // Add more client routes here.
    });
