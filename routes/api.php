<?php

use App\Http\Controllers\Family\AuthController;
use App\Http\Controllers\Family\PortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/family')->name('family.api.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1,family-api-login');
    Route::post('activate', [AuthController::class, 'activate'])->middleware('throttle:6,1,family-api-activate');
    Route::middleware(['auth:sanctum', 'family.access', 'throttle:120,1,family-api'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [PortalController::class, 'home']);
        Route::get('children/{child}', [PortalController::class, 'show']);
        Route::post('children/{child}/messages', [PortalController::class, 'message'])->middleware('throttle:10,1,family-api-messages');
        Route::post('children/{child}/incidents/{incident}/acknowledge', [PortalController::class, 'acknowledge']);
        Route::post('children/{child}/tasks/{task}/complete', [PortalController::class, 'complete']);
        Route::post('children/{child}/absences', [PortalController::class, 'absence'])->middleware('throttle:10,1,family-api-absences');
        Route::post('children/{child}/pickup-token', [PortalController::class, 'pickup'])->middleware('throttle:6,1,family-api-pickup');
    });
});
