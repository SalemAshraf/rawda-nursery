<?php

use App\Http\Controllers\PlatformController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform')->name('platform.')->group(function () {
    Route::get('/login', [PlatformController::class, 'loginForm'])->name('login');
    Route::post('/login', [PlatformController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::middleware('auth:platform')->group(function () {
        Route::get('/', [PlatformController::class, 'index'])->name('index');
        Route::post('/logout', [PlatformController::class, 'logout'])->name('logout');
        Route::post('/plans', [PlatformController::class, 'savePlan'])->name('plans.store');
        Route::put('/plans/{plan}', [PlatformController::class, 'savePlan'])->name('plans.update');
        Route::put('/nurseries/{tenant}/subscription', [PlatformController::class, 'updateSubscription'])->name('subscriptions.update');
        Route::put('/inquiries/{inquiry}', [PlatformController::class, 'updateInquiry'])->name('inquiries.update');
    });
});
