<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\SkipCsrfForPayment;
use App\Models\Invoice;

Route::get('/', function () {
    return redirect('/admin');
});
Route::get('/test', function () {
   echo 'test';
    // return view('welcome');
});
Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('optimize:clear');
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('view:clear');
    $exitCode = Artisan::call('route:clear');
    $exitCode = Artisan::call('config:clear');
    $exitCode = Artisan::call('config:cache');
    // $exitCode = Artisan::call('view:cache');
    // $exitCode = Artisan::call('storage:link');
    // $exitCode = Artisan::call('migrate');




    return 'DONE';
});

Route::get('/test2', [\App\Http\Controllers\MaintenanceRequestController::class, 'testpay']);
// Route::post('/payment/callback1', [\App\Http\Controllers\MaintenanceRequestController::class, 'paymentCallback'])->middleware(SkipCsrfForPayment::class)->name('payment.callback1');
Route::post('/payment/callback1', [\App\Http\Controllers\MaintenanceRequestController::class, 'paymentCallback'])->name('payment.callback1');

Route::get('/admin/sales-invoices/{invoice}/print', function (Invoice $invoice) {
    abort_unless(
        auth()->user()?->can('view_sales::invoice') || auth()->user()?->can('view_any_sales::invoice'),
        403,
    );

    $invoice->load([
        'maintenanceRequest.customer',
        'maintenanceRequest.address.city',
        'maintenanceRequest.address.district',
        'services',
        'spareParts',
    ]);

    return view('sales-invoices.print', compact('invoice'));
})->middleware('auth')->name('admin.sales-invoices.print');
