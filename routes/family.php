<?php

use App\Http\Controllers\Family\AuthController;
use App\Http\Controllers\Family\PortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('family')->name('family.')->group(function () {
    Route::get('login', [AuthController::class, 'page'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('activate', [AuthController::class, 'page'])->name('activate');
    Route::post('activate', [AuthController::class, 'activate'])->middleware('throttle:6,1');
    Route::middleware(['auth:family', 'family.access'])->group(function () {
        Route::get('/', [PortalController::class, 'home'])->name('home');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('children/{child}', [PortalController::class, 'show'])->name('child');
        Route::post('children/{child}/messages', [PortalController::class, 'message'])->middleware('throttle:10,1')->name('message');
        Route::post('children/{child}/incidents/{incident}/acknowledge', [PortalController::class, 'acknowledge'])->name('acknowledge');
        Route::post('children/{child}/tasks/{task}/complete', [PortalController::class, 'complete'])->name('complete');
        Route::post('children/{child}/absences', [PortalController::class, 'absence'])->middleware('throttle:10,1')->name('absence');
        Route::post('children/{child}/absences/{absence}/cancel', [PortalController::class, 'cancelAbsence'])->middleware('throttle:10,1,family-absence-cancel')->name('absence.cancel');
        Route::post('children/{child}/pickup-token', [PortalController::class, 'pickup'])->middleware('throttle:6,1')->name('pickup');
    });
});
