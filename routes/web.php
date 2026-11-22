<?php

use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\AiSettingsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CommercialController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Family\PickupController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\McpController;
use App\Http\Controllers\OauthController;
use App\Http\Controllers\PickupPersonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StaffController;
use App\Http\Middleware\SubscriptionAccess;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SearchController::class, 'sitemap'])->name('sitemap');
Route::get('/', [CommercialController::class, 'landing'])->name('home');
Route::post('/sales-inquiries', [CommercialController::class, 'inquiry'])->middleware('throttle:5,10')->name('sales.inquiry');
require __DIR__.'/platform.php';

Route::middleware(['auth:web', SubscriptionAccess::class])->group(function () {
    Route::get('/pickup-scan', [PickupController::class, 'page'])->name('pickup.scan');
    Route::post('/pickup-scan/verify', [PickupController::class, 'verify'])->middleware('throttle:20,1')->name('pickup.verify');
    Route::post('/pickup-scan/checkout', [PickupController::class, 'checkout'])->middleware('throttle:10,1')->name('pickup.checkout');
    Route::get('/families', [App\Http\Controllers\Family\StaffController::class, 'index'])->name('families.index');
    Route::post('/families/invite', [App\Http\Controllers\Family\StaffController::class, 'invite'])->middleware('throttle:10,1')->name('families.invite');
    Route::post('/families/access/{access}/revoke', [App\Http\Controllers\Family\StaffController::class, 'revoke'])->name('families.revoke');
    Route::post('/families/publish/{type}/{record}', [App\Http\Controllers\Family\StaffController::class, 'publish'])->name('families.publish');
    Route::post('/families/reply', [App\Http\Controllers\Family\StaffController::class, 'reply'])->name('families.reply');
    Route::post('/families/announcements', [App\Http\Controllers\Family\StaffController::class, 'announcement'])->name('families.announcement');
    Route::post('/families/tasks', [App\Http\Controllers\Family\StaffController::class, 'task'])->name('families.task');
    Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
    Route::post('/setup', [SetupController::class, 'update'])->name('setup.update');
    Route::get('/subscription', [CommercialController::class, 'subscription'])->name('subscription.index');
    Route::post('/subscription/request', [CommercialController::class, 'requestPlan'])->middleware('throttle:5,10')->name('subscription.request');
    Route::get('/reports', ReportsController::class)->name('reports');
    Route::get('/admissions', [AdmissionController::class, 'index'])->name('admissions.index');
    Route::post('/admissions', [AdmissionController::class, 'store'])->name('admissions.store');
    Route::put('/admissions/{admission}', [AdmissionController::class, 'update'])->name('admissions.update');
    Route::post('/admissions/{admission}/enroll', [AdmissionController::class, 'enroll'])->name('admissions.enroll');
    Route::get('/audit', AuditController::class)->name('audit.index');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/children', [ChildController::class, 'index'])->name('children.index');
    Route::post('/children', [ChildController::class, 'store'])->name('children.store');
    Route::get('/children/{child}', [ChildController::class, 'show'])->name('children.show');
    Route::put('/children/{child}', [ChildController::class, 'update'])->name('children.update');

    Route::post('/children/{child}/guardians', [GuardianController::class, 'store'])->name('guardians.store');
    Route::post('/children/{child}/pickups', [PickupPersonController::class, 'store'])->name('pickups.store');
    Route::delete('/pickups/{pickupPerson}', [PickupPersonController::class, 'destroy'])->name('pickups.destroy');

    Route::get('/classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
    Route::put('/classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
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

// Protocol endpoints use bearer OAuth, never the browser session for data access.
Route::get('/.well-known/oauth-protected-resource/{any?}', [OauthController::class, 'resourceMetadata'])->where('any', '.*');
Route::get('/.well-known/oauth-authorization-server/{any?}', [OauthController::class, 'serverMetadata'])->where('any', '.*');
Route::post('/oauth/register', [OauthController::class, 'register'])->middleware('throttle:20,1');
Route::post('/oauth/token', [OauthController::class, 'token'])->middleware('throttle:60,1');
Route::post('/oauth/revoke', [OauthController::class, 'revoke'])->middleware('throttle:60,1');
Route::post('/mcp', [McpController::class, 'handle'])->middleware('throttle:60,1')->name('mcp');
Route::middleware('auth:web')->group(function () {
    Route::get('/oauth/authorize', [OauthController::class, 'authorizeForm'])->name('oauth.authorize');
    Route::post('/oauth/authorize', [OauthController::class, 'decide'])->name('oauth.decide');
    Route::get('/ai', [AiSettingsController::class, 'index'])->name('ai.index');
    Route::post('/ai', [AiSettingsController::class, 'update'])->name('ai.update');
    Route::delete('/ai/connections/{family}', [AiSettingsController::class, 'revoke'])->name('ai.revoke');
});

require __DIR__.'/family.php';
