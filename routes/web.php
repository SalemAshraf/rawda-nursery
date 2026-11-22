<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\PickupPersonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/children', [ChildController::class, 'index'])->name('children.index');
    Route::post('/children', [ChildController::class, 'store'])->name('children.store');
    Route::get('/children/{child}', [ChildController::class, 'show'])->name('children.show');
    Route::put('/children/{child}', [ChildController::class, 'update'])->name('children.update');

    Route::post('/children/{child}/guardians', [GuardianController::class, 'store'])->name('guardians.store');
    Route::post('/children/{child}/pickups', [PickupPersonController::class, 'store'])->name('pickups.store');
    Route::delete('/pickups/{pickupPerson}', [PickupPersonController::class, 'destroy'])->name('pickups.destroy');

    Route::post('/classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/{child}/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/{child}/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');

    Route::get('/logs', [DailyLogController::class, 'index'])->name('logs.index');
    Route::post('/logs/{child}', [DailyLogController::class, 'upsert'])->name('logs.upsert');

    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::post('/children/{child}/incidents', [IncidentController::class, 'store'])->name('incidents.store');
    Route::post('/incidents/{incident}/acknowledge', [IncidentController::class, 'acknowledge'])->name('incidents.acknowledge');

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/plans', [BillingController::class, 'storePlan'])->name('billing.plans.store');
    Route::post('/billing/invoices', [BillingController::class, 'storeInvoice'])->name('billing.invoices.store');
    Route::post('/billing/invoices/{invoice}/send', [BillingController::class, 'sendInvoice'])->name('billing.invoices.send');
    Route::post('/billing/invoices/{invoice}/void', [BillingController::class, 'voidInvoice'])->name('billing.invoices.void');
    Route::post('/billing/invoices/{invoice}/payments', [BillingController::class, 'storePayment'])->name('billing.payments.store');

    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
