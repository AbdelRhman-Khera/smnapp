<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\SkipCsrfForPayment;

Route::get('/', function () {
    return redirect('/admin');
});
Route::get('/test', function () {
   echo 'test';
    // return view('welcome');
});
Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('config:cache');
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('storage:link');
    $exitCode = Artisan::call('optimize:clear');



    return 'DONE';
});

Route::get('/test2', [\App\Http\Controllers\MaintenanceRequestController::class, 'testpay']);
// Route::post('/payment/callback1', [\App\Http\Controllers\MaintenanceRequestController::class, 'paymentCallback'])->middleware(SkipCsrfForPayment::class)->name('payment.callback1');
Route::post('/payment/callback1', [\App\Http\Controllers\MaintenanceRequestController::class, 'paymentCallback'])->name('payment.callback1');

